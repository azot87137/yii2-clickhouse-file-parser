# Deploy

- `docker-compose build`
- `docker-compose run --rm php composer install`
- `docker-compose up -d`
- `docker-compose run --rm php php yii clickhouse-migrate`

# Usage

- `docker-compose exec php php yii import-logs`
- `docker-compose exec php php yii get-logs "2023-10-11 16:13:10" "2023-12-11 17:22:00"`
- `docker-compose exec php php yii get-logs/count "2023-10-11 16:13:10" "2023-12-11 17:22:00"`

The import saves the file's position data in ClickHouse. After an interruption, it can resume from the same spot.
You can specify the file path during the import to work with multiple files.