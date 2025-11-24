# Task Optimizer Service

Go-микросервис для интеллектуального распределения задач между исполнителями.

## Описание

Сервис автоматически назначает задачи пользователям на основе:
- **Навыков** (skill matching): совпадение навыков пользователя с требованиями задачи
- **Загруженности** (workload): текущее количество активных задач у пользователя
- **Приоритета задачи**: более высокоприоритетные задачи получают бонус при расчете

## Архитектура

Проект построен на принципах **Clean Architecture**:

```
├── cmd/server/          # Точка входа приложения
├── internal/
│   ├── domain/          # Бизнес-логика (entities, services, interfaces)
│   ├── application/     # Use cases (orchestration)
│   ├── infrastructure/  # Реализации (PostgreSQL, RabbitMQ, config)
│   └── interfaces/      # Transport layer (handlers)
└── pkg/                 # Переиспользуемые пакеты
```

## Алгоритм scoring

Итоговый score рассчитывается по формуле:

```
Total Score = (Skill Match × 0.4) + (Load Score × 0.4) + (Priority Bonus × 0.2)
```

Где:
- **Skill Match** (0.0-1.0): процент совпадения навыков пользователя с требованиями задачи
- **Load Score** (0.0-1.0): инверсия загруженности (0% load = 1.0 score)
- **Priority Bonus** (0.0-1.0): нормализованный приоритет задачи (1-5)

## Технологии

- **Go 1.22+**
- **PostgreSQL** - чтение данных о пользователях
- **RabbitMQ** - асинхронная коммуникация с Laravel
- **Zap** - структурированное логирование
- **Testify** - unit тестирование

## Запуск

### Через Docker Compose (рекомендуется)

```bash
# Из корня проекта
docker-compose up -d task-optimizer
```

### Локально

```bash
# Установить зависимости
go mod download

# Скопировать .env
cp .env.example .env

# Запустить
go run cmd/server/main.go
```

## Тестирование

```bash
# Запустить все тесты
go test ./...

# С покрытием
go test -cover ./...

# Verbose режим
go test -v ./...
```

## Переменные окружения

| Переменная | Описание | По умолчанию |
|-----------|----------|--------------|
| `DB_HOST` | PostgreSQL хост | `postgres` |
| `DB_PORT` | PostgreSQL порт | `5432` |
| `DB_USER` | PostgreSQL пользователь | `smart_task_user` |
| `DB_PASSWORD` | PostgreSQL пароль | `secret` |
| `DB_NAME` | PostgreSQL база данных | `smart_task_db` |
| `RABBITMQ_URL` | RabbitMQ connection string | `amqp://guest:guest@rabbitmq:5672/` |
| `RABBITMQ_EXCHANGE` | RabbitMQ exchange | `tasks` |
| `RABBITMQ_QUEUE_TASK_CREATED` | Queue для входящих событий | `task.created` |
| `RABBITMQ_QUEUE_TASK_ASSIGNED` | Queue для исходящих событий | `task.assigned` |
| `LOG_LEVEL` | Уровень логирования | `info` |
| `WORKER_COUNT` | Количество воркеров | `5` |

## События

### Входящие события (task.created)

```json
{
  "task_id": 1,
  "title": "Implement feature",
  "description": "...",
  "priority": 5,
  "project_id": 1,
  "skills": ["php", "laravel"],
  "created_at": "2025-11-24T12:00:00Z"
}
```

### Исходящие события (task.assigned)

```json
{
  "task_id": 1,
  "assignee_id": 3,
  "score": 0.85,
  "reason": "Skill match: 100%, Load: 2/10, Priority: 5",
  "assigned_at": "2025-11-24T12:00:01Z"
}
```

## Graceful Shutdown

Сервис корректно обрабатывает сигналы `SIGINT` и `SIGTERM`, завершая обработку текущих сообщений перед остановкой.

