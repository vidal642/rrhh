#!/bin/bash

echo "Iniciando optimización para producción..."

# 1. Instalar dependencias sin paquetes de desarrollo (opcional)
# composer install --optimize-autoloader --no-dev

# 2. Limpiar cachés anteriores
echo "Limpiando cachés..."
php artisan cache:clear
php artisan config:clear
php artisan route:clear
php artisan view:clear

# 3. Cachear configuración y rutas (aumenta el rendimiento)
echo "Generando cachés de producción..."
php artisan config:cache
php artisan route:cache
php artisan view:cache

# 4. Compilar eventos y eventos
php artisan event:cache

echo "¡Optimización de Laravel completada con éxito!"
echo ""
echo "Recuerde habilitar OPcache en su php.ini para obtener el máximo rendimiento:"
echo "  opcache.enable=1"
echo "  opcache.memory_consumption=128"
echo "  opcache.interned_strings_buffer=8"
echo "  opcache.max_accelerated_files=10000"
echo "  opcache.revalidate_freq=0  (O '2' en desarrollo)"
echo "  opcache.save_comments=1"
