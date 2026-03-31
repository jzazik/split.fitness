# Инструкция по настройке деплоя

## Настройка GitHub Secrets

Для работы автоматического деплоя нужно добавить следующие секреты в настройках GitHub репозитория:

1. Перейдите в Settings → Secrets and variables → Actions
2. Нажмите "New repository secret" и добавьте:

### Обязательные секреты:

- **SSH_HOST** - IP адрес или домен вашего сервера (например: `123.45.67.89` или `server.example.com`)
- **SSH_USERNAME** - имя пользователя для SSH подключения (например: `root` или `deploy`)
- **SSH_PRIVATE_KEY** - приватный SSH ключ для подключения к серверу
- **PROJECT_PATH** - полный путь к проекту на сервере (например: `/var/www/split.fitness`)

### Опциональные секреты:

- **SSH_PORT** - порт SSH (по умолчанию 22)

## Как получить SSH ключ

Если у вас еще нет SSH ключа для деплоя:

```bash
# На вашем локальном компьютере
ssh-keygen -t ed25519 -C "github-deploy" -f ~/.ssh/github_deploy

# Скопируйте публичный ключ на сервер
ssh-copy-id -i ~/.ssh/github_deploy.pub user@your-server.com

# Или вручную добавьте содержимое в ~/.ssh/authorized_keys на сервере
```

Затем скопируйте содержимое приватного ключа:
```bash
cat ~/.ssh/github_deploy
```

И добавьте его в GitHub Secret `SSH_PRIVATE_KEY`.

## Настройка на сервере

На вашем VPS должно быть установлено:

- PHP 8.1+
- Composer
- Node.js 18+
- Git
- Все необходимые PHP расширения для Laravel

Убедитесь что проект склонирован на сервер:
```bash
cd /var/www
git clone https://github.com/jzazik/split.fitness.git
cd split.fitness
cp .env.example .env
# Настройте .env файл
php artisan key:generate
```

## Как работает деплой

При каждом push в ветку `main`:
1. GitHub Actions подключается к серверу по SSH
2. Делает `git pull` последних изменений
3. Устанавливает PHP зависимости
4. Собирает фронтенд (npm run build)
5. Запускает миграции базы данных
6. Очищает и обновляет кэш Laravel

## Проверка деплоя

После настройки сделайте тестовый коммит:
```bash
git add .github
git commit -m "Add GitHub Actions deploy workflow"
git push origin main
```

Проверьте статус деплоя в разделе "Actions" вашего репозитория на GitHub.
