# Task Optimizer Service

Go microservice for intelligent task assignment.

## Description

The service automatically assigns tasks to users based on:
- **Skills** (skill matching): matching user skills with task requirements
- **Workload**: current number of active tasks for the user
- **Task Priority**: higher priority tasks receive a bonus in calculation

## Architecture

Built with **Clean Architecture** principles:

```
├── cmd/server/          # Application entry point
├── internal/
│   ├── domain/          # Business logic (entities, services, interfaces)
│   ├── application/     # Use cases (orchestration)
│   ├── infrastructure/  # Implementations (PostgreSQL, RabbitMQ, config)
│   └── interfaces/      # Transport layer (handlers)
└── pkg/                 # Reusable packages
```

## Scoring Algorithm

Total score is calculated using the formula:

```
Total Score = (Skill Match × 0.4) + (Load Score × 0.4) + (Priority Bonus × 0.2)
```

Where:
- **Skill Match** (0.0-1.0): percentage of user skills matching task requirements
- **Load Score** (0.0-1.0): inverse of workload (0% load = 1.0 score)
- **Priority Bonus** (0.0-1.0): normalized task priority (1-5)

## Technologies

- **Go 1.25+**
- **PostgreSQL** - user data access
- **RabbitMQ** - asynchronous communication with Laravel
- **Zap** - structured logging
- **Testify** - unit testing

## Running

### Via Docker Compose (recommended)

```bash
# From project root
docker-compose up -d task-optimizer
```

### Locally

```bash
# Install dependencies
go mod download

# Copy environment file
cp .env.example .env

# Run
go run cmd/server/main.go
```

## Testing

```bash
# Run all tests
go test ./...

# With coverage
go test -cover ./...

# Verbose mode
go test -v ./...
```

## Environment Variables

| Variable | Description | Default |
|----------|-------------|---------|
| `DB_HOST` | PostgreSQL host | `postgres` |
| `DB_PORT` | PostgreSQL port | `5432` |
| `DB_USER` | PostgreSQL user | `smart_task_user` |
| `DB_PASSWORD` | PostgreSQL password | `secret` |
| `DB_NAME` | PostgreSQL database | `smart_task_db` |
| `RABBITMQ_URL` | RabbitMQ connection string | `amqp://guest:guest@rabbitmq:5672/` |
| `RABBITMQ_EXCHANGE` | RabbitMQ exchange | `tasks` |
| `RABBITMQ_QUEUE_TASK_CREATED` | Queue for incoming events | `task.created` |
| `RABBITMQ_QUEUE_TASK_ASSIGNED` | Queue for outgoing events | `task.assigned` |
| `LOG_LEVEL` | Logging level | `info` |
| `WORKER_COUNT` | Number of workers | `5` |

## Events

### Incoming Events (task.created)

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

### Outgoing Events (task.assigned)

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

The service properly handles `SIGINT` and `SIGTERM` signals, completing processing of current messages before shutdown.
