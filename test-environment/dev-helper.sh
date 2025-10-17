#!/bin/bash

# Entwicklungshilfen für Bricks to Etch Migration

case "$1" in
    "logs")
        echo "📋 Zeige Container-Logs..."
        docker-compose logs -f
        ;;
    "shell-bricks")
        echo "🐚 Öffne Shell auf Bricks Container..."
        docker exec -it b2e-bricks bash
        ;;
    "shell-etch")
        echo "🐚 Öffne Shell auf Etch Container..."
        docker exec -it b2e-etch bash
        ;;
    "mysql-bricks")
        echo "🗄️  Öffne MySQL für Bricks Site..."
        docker exec -it b2e-mysql-bricks mysql -u wordpress -pwordpress wordpress_bricks
        ;;
    "mysql-etch")
        echo "🗄️  Öffne MySQL für Etch Site..."
        docker exec -it b2e-mysql-etch mysql -u wordpress -pwordpress wordpress_etch
        ;;
    "status")
        echo "📊 Container-Status:"
        docker-compose ps
        ;;
    "cleanup")
        echo "🧹 Docker-Cleanup..."
        docker system prune -f
        docker volume prune -f
        ;;
    *)
        echo "🛠️  Bricks to Etch Migration - Entwicklungshilfen"
        echo ""
        echo "Verwendung: $0 <command>"
        echo ""
        echo "Befehle:"
        echo "  logs          - Zeige alle Container-Logs"
        echo "  shell-bricks  - Shell auf Bricks Container"
        echo "  shell-etch    - Shell auf Etch Container"
        echo "  mysql-bricks  - MySQL für Bricks Site"
        echo "  mysql-etch    - MySQL für Etch Site"
        echo "  status        - Container-Status"
        echo "  cleanup       - Docker-Cleanup"
        echo ""
        echo "Beispiele:"
        echo "  $0 logs"
        echo "  $0 shell-bricks"
        echo "  $0 mysql-etch"
        ;;
esac
