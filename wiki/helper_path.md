# パスの取得ヘルパー

## これがベース

```php
$path = base_path(); // -> /var/www
dump($path);
```

中身は以下のようになっている

```php
return app()->basePath($path);
```

## appのパス

```php
$path = base_path('app'); // -> /var/www/app
$path = app_path();       // -> /var/www/app
dump($path);
```

## configのパス

```php
$path = base_path('config'); // -> /var/www/app
$path = config_path();       // -> /var/www/app
dump($path);
```

## databaseのパス

```php
$path = base_path('database'); // -> /var/www/database
$path = database_path();       // -> /var/www/database
dump($path);
```

## publicのパス

```php
$path = base_path('public'); // -> /var/www/public
$path = public_path();       // -> /var/www/public
dump($path);
```

## resourcesのパス

```php
$path = base_path('resources'); // -> /var/www/resources
$path = resource_path();        // -> /var/www/resources
dump($path);
```

## storageのパス

```php
$path = base_path('storage'); // -> /var/www/storage
$path = storage_path();       // -> /var/www/storage
dump($path);
```

## 

basename()
