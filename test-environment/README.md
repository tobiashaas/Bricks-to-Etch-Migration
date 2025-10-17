# Bricks to Etch Migration - Test Environment

## 🚀 Automatische Entwicklungsumgebung mit 2 WordPress-Instanzen

### 📁 Struktur
```
test-environment/
├── README.md                    # Diese Datei
├── setup.sh                     # Automatisches Setup-Script
├── sync-plugin.sh               # Plugin-Synchronisation
├── start.sh                     # Umgebung starten
├── stop.sh                      # Umgebung stoppen
├── reset.sh                     # Umgebung zurücksetzen
├── wordpress-bricks/            # WordPress mit Bricks (Source)
│   ├── wp-content/
│   │   └── plugins/
│   │       └── bricks-etch-migration -> ../../bricks-etch-migration (Symlink)
│   └── ...
└── wordpress-etch/              # WordPress mit Etch (Target)
    ├── wp-content/
    │   └── plugins/
    │       └── bricks-etch-migration -> ../../bricks-etch-migration (Symlink)
    └── ...
```

### 🎯 Features
- **Automatische Plugin-Synchronisation** via Symlinks
- **2 separate WordPress-Instanzen** (Bricks + Etch)
- **Docker-basiert** für einfache Verwaltung
- **Ein-Klick Setup** für neue Entwickler
- **Automatische Plugin-Installation**

### 🚀 Quick Start
```bash
cd test-environment
./setup.sh    # Einmaliges Setup
./start.sh    # Umgebung starten
```

### 🔄 Plugin-Synchronisation
```bash
./sync-plugin.sh    # Plugin manuell synchronisieren (automatisch bei Änderungen)
```

### 🔧 PHP Upload-Limits
```bash
./fix-php-limits.sh    # PHP Upload-Limits erhöhen (automatisch beim Setup)
```

**Standard-Limits:**
- **Upload:** 256M upload_max_filesize
- **Memory:** 512M memory_limit
- **Execution Time:** 300s max_execution_time
- **Input Vars:** 3000 max_input_vars

### 🧹 Cleanup
```bash
./reset.sh    # Komplette Umgebung zurücksetzen
./stop.sh     # Umgebung stoppen
```
