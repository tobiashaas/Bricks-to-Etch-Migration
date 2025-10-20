# Refactoring Plan - Modulare Architektur

**Ziel:** Wartbare, skalierbare, modulare Codebase mit klaren Verantwortlichkeiten

---

## Aktuelle Struktur (Probleme)

### ❌ Monolithische Klassen
- `css_converter.php` - 1365 Zeilen, macht zu viel
- `gutenberg_generator.php` - 1424 Zeilen, zu komplex
- `admin_interface.php` - 2211 Zeilen, UI + Logik gemischt

### ❌ Tight Coupling
- Klassen rufen direkt WordPress-Funktionen auf
- Keine Dependency Injection
- Schwer zu testen

### ❌ Fehlende Abstraktion
- Keine Interfaces
- Direkte Datenbankzugriffe überall
- Keine Service-Layer

---

## Ziel-Architektur

```
bricks-etch-migration/
├── src/
│   ├── Core/
│   │   ├── Plugin.php                    # Main Plugin Class
│   │   ├── Container.php                 # DI Container
│   │   └── ServiceProvider.php           # Service Registration
│   │
│   ├── Services/
│   │   ├── CSS/
│   │   │   ├── CSSConverterService.php
│   │   │   ├── StyleMapService.php
│   │   │   └── SelectorGenerator.php
│   │   │
│   │   ├── Content/
│   │   │   ├── ContentMigrationService.php
│   │   │   ├── GutenbergBlockGenerator.php
│   │   │   └── ElementMapper.php
│   │   │
│   │   ├── API/
│   │   │   ├── APIClientService.php
│   │   │   ├── RequestBuilder.php
│   │   │   └── ResponseHandler.php
│   │   │
│   │   └── Storage/
│   │       ├── StyleRepository.php
│   │       ├── ContentRepository.php
│   │       └── OptionRepository.php
│   │
│   ├── Interfaces/
│   │   ├── ConverterInterface.php
│   │   ├── RepositoryInterface.php
│   │   └── ServiceInterface.php
│   │
│   ├── DTOs/
│   │   ├── StyleDTO.php
│   │   ├── ElementDTO.php
│   │   └── MigrationResultDTO.php
│   │
│   └── UI/
│       ├── AdminPage.php
│       ├── AjaxHandler.php
│       └── AssetManager.php
│
└── includes/  # Legacy (wird schrittweise migriert)
```

---

## Phase 1: Interfaces & DTOs

### 1.1 Interfaces erstellen

**ConverterInterface.php**
```php
interface ConverterInterface {
    public function convert(array $input): array;
    public function validate(array $input): bool;
}
```

**RepositoryInterface.php**
```php
interface RepositoryInterface {
    public function find(string $id): ?array;
    public function save(string $id, array $data): bool;
    public function delete(string $id): bool;
}
```

**ServiceInterface.php**
```php
interface ServiceInterface {
    public function execute(array $params): mixed;
}
```

### 1.2 DTOs erstellen

**StyleDTO.php**
```php
class StyleDTO {
    public function __construct(
        public readonly string $id,
        public readonly string $selector,
        public readonly string $css,
        public readonly string $type,
        public readonly bool $readonly = false
    ) {}
    
    public static function fromArray(array $data): self {
        return new self(
            $data['id'],
            $data['selector'],
            $data['css'],
            $data['type'],
            $data['readonly'] ?? false
        );
    }
    
    public function toArray(): array {
        return [
            'selector' => $this->selector,
            'css' => $this->css,
            'type' => $this->type,
            'readonly' => $this->readonly
        ];
    }
}
```

---

## Phase 2: Repository Layer

### 2.1 StyleRepository

**Verantwortung:** Alle Datenbankzugriffe für Styles

```php
class StyleRepository implements RepositoryInterface {
    private string $option_name = 'etch_styles';
    
    public function find(string $id): ?array {
        $styles = get_option($this->option_name, []);
        return $styles[$id] ?? null;
    }
    
    public function findAll(): array {
        return get_option($this->option_name, []);
    }
    
    public function save(string $id, array $data): bool {
        $styles = $this->findAll();
        $styles[$id] = $data;
        return update_option($this->option_name, $styles);
    }
    
    public function saveMany(array $styles): bool {
        $existing = $this->findAll();
        $merged = array_merge($existing, $styles);
        return update_option($this->option_name, $merged);
    }
    
    public function delete(string $id): bool {
        $styles = $this->findAll();
        unset($styles[$id]);
        return update_option($this->option_name, $styles);
    }
}
```

### 2.2 StyleMapRepository

```php
class StyleMapRepository {
    private string $option_name = 'b2e_style_map';
    
    public function getMapping(string $bricksId): ?string {
        $map = $this->getAll();
        return $map[$bricksId] ?? null;
    }
    
    public function getAll(): array {
        return get_option($this->option_name, []);
    }
    
    public function save(array $map): bool {
        return update_option($this->option_name, $map);
    }
}
```

---

## Phase 3: Service Layer

### 3.1 CSSConverterService

**Verantwortung:** CSS-Konvertierung orchestrieren

```php
class CSSConverterService implements ServiceInterface {
    public function __construct(
        private StyleRepository $styleRepo,
        private StyleMapRepository $mapRepo,
        private SelectorGenerator $selectorGen,
        private IDGenerator $idGen
    ) {}
    
    public function execute(array $params): array {
        $bricksClasses = $params['classes'];
        
        $styles = [];
        $map = [];
        
        foreach ($bricksClasses as $class) {
            $etchId = $this->idGen->generate();
            $style = $this->convertClass($class);
            
            $styles[$etchId] = $style;
            $map[$class['id']] = $etchId;
        }
        
        return [
            'styles' => $styles,
            'style_map' => $map
        ];
    }
    
    private function convertClass(array $class): array {
        return [
            'selector' => $this->selectorGen->generate($class['name']),
            'css' => $class['css'],
            'type' => 'class',
            'readonly' => false
        ];
    }
}
```

### 3.2 StyleMapService

**Verantwortung:** Style-Map-Operationen

```php
class StyleMapService {
    public function __construct(
        private StyleMapRepository $repo
    ) {}
    
    public function resolveStyleIds(array $bricksIds): array {
        $etchIds = [];
        
        foreach ($bricksIds as $bricksId) {
            $etchId = $this->repo->getMapping($bricksId);
            if ($etchId) {
                $etchIds[] = $etchId;
            }
        }
        
        return $etchIds;
    }
    
    public function createMapping(array $styles): array {
        $map = [];
        
        foreach ($styles as $etchId => $style) {
            if (isset($style['_bricks_id'])) {
                $map[$style['_bricks_id']] = $etchId;
            }
        }
        
        return $map;
    }
}
```

---

## Phase 4: Dependency Injection

### 4.1 Container

```php
class Container {
    private array $services = [];
    
    public function register(string $id, callable $factory): void {
        $this->services[$id] = $factory;
    }
    
    public function get(string $id): mixed {
        if (!isset($this->services[$id])) {
            throw new Exception("Service not found: $id");
        }
        
        $factory = $this->services[$id];
        return $factory($this);
    }
}
```

### 4.2 ServiceProvider

```php
class ServiceProvider {
    public function register(Container $container): void {
        // Repositories
        $container->register('style_repo', fn() => new StyleRepository());
        $container->register('map_repo', fn() => new StyleMapRepository());
        
        // Services
        $container->register('css_converter', fn($c) => new CSSConverterService(
            $c->get('style_repo'),
            $c->get('map_repo'),
            $c->get('selector_gen'),
            $c->get('id_gen')
        ));
        
        $container->register('style_map_service', fn($c) => new StyleMapService(
            $c->get('map_repo')
        ));
    }
}
```

---

## Phase 5: Migration Strategy

### Schritt 1: Neue Struktur parallel aufbauen
- ✅ Interfaces erstellen
- ✅ DTOs erstellen
- ✅ Repositories erstellen
- ✅ Services erstellen

### Schritt 2: Adapter-Pattern für Legacy-Code
```php
class LegacyCSSConverterAdapter {
    public function __construct(
        private CSSConverterService $newService
    ) {}
    
    public function convert_bricks_classes_to_etch() {
        // Legacy-Aufruf → Neuer Service
        $bricksClasses = get_option('bricks_global_classes', []);
        return $this->newService->execute(['classes' => $bricksClasses]);
    }
}
```

### Schritt 3: Schrittweise migrieren
1. CSS-Konvertierung
2. Content-Migration
3. API-Client
4. Admin-Interface

### Schritt 4: Legacy-Code entfernen
- Alte Klassen löschen
- Tests anpassen
- Dokumentation aktualisieren

---

## Benefits

### ✅ Wartbarkeit
- Klare Verantwortlichkeiten
- Kleine, fokussierte Klassen
- Einfach zu verstehen

### ✅ Testbarkeit
- Dependency Injection
- Interfaces für Mocking
- Isolierte Units

### ✅ Skalierbarkeit
- Neue Features einfach hinzufügen
- Services austauschbar
- Flexible Architektur

### ✅ Code-Qualität
- Type Safety mit DTOs
- Weniger Bugs
- Bessere IDE-Unterstützung

---

## Nächste Schritte

1. **Phase 1 starten:** Interfaces & DTOs erstellen
2. **Tests schreiben:** Für neue Services
3. **Schrittweise migrieren:** Ein Modul nach dem anderen
4. **Dokumentation:** Für jedes neue Modul

**Zeitaufwand:** ~2-3 Tage für vollständiges Refactoring
