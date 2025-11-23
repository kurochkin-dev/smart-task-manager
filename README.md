# Smart Task Manager

Intelligent task tracker with automatic task distribution based on skills, workload, and priorities. This project demonstrates modern web development practices including microservices architecture, event-driven design, real-time updates, and comprehensive monitoring.

## Project Overview

Smart Task Manager is a full-stack application that automatically assigns tasks to team members based on their skills, current workload, and task priorities. The system uses intelligent algorithms (implemented in Go microservices) to optimize task distribution, ensuring efficient workload balancing across the team.

### Key Features

- **Intelligent Task Assignment**: Automatic task distribution using scoring algorithms based on skills, workload, and priorities
- **Real-time Updates**: WebSocket-based notifications for task assignments and status changes
- **Role-Based Access Control**: Admin, Manager, and User roles with granular permissions
- **Comprehensive API**: RESTful API with full Swagger/OpenAPI documentation
- **Event-Driven Architecture**: Asynchronous task processing using message queues
- **Monitoring & Observability**: Prometheus metrics and Grafana dashboards
- **Microservices**: Heavy computation tasks handled by Go services

## Tech Stack

### Backend
- **PHP 8.4** + **Laravel 12** - Main application framework
- **Go** - Microservices for heavy computation (task assignment algorithms)
- **PostgreSQL 17** - Primary database
- **Redis** - Caching and session storage
- **RabbitMQ** - Message queue for asynchronous processing

### Frontend
- **React** + **TypeScript** - Modern SPA interface
- **WebSocket** - Real-time updates via Laravel Broadcasting

### DevOps & Tools
- **Docker** + **docker-compose** - Containerization
- **GitHub Actions** - CI/CD pipeline
- **Prometheus** + **Grafana** - Monitoring and metrics
- **Swagger/OpenAPI** - API documentation

## Architecture

### High-Level Design

```
┌─────────────┐     ┌──────────────┐     ┌─────────────┐
│   React     │────▶│   Laravel    │────▶│ PostgreSQL  │
│  Frontend   │◀────│   (Monolith) │◀────│   Database  │
└─────────────┘     └──────────────┘     └─────────────┘
                            │
                            │ Events
                            ▼
                    ┌──────────────┐
                    │  RabbitMQ    │
                    │   Queue      │
                    └──────────────┘
                            │
                            ▼
                    ┌──────────────┐
                    │  Go Service  │
                    │ (Task Assign)│
                    └──────────────┘
```

### Architecture Principles

- **Monolith + Microservices**: Laravel handles CRUD, auth, and UI; Go services handle heavy computation
- **Clean Architecture**: Separation of transport (controllers), business logic (services), and infrastructure (repositories)
- **Event-Driven**: Domain events for asynchronous integration between services
- **Idempotency**: All event handlers are idempotent for reliability
- **Observability**: Comprehensive logging, metrics, and tracing

## Installation

### Prerequisites

- Docker and Docker Compose
- Git

### Setup Steps

1. **Clone the repository**
   ```bash
   git clone <repository-url>
   cd smart-task-manager
   ```

2. **Configure environment**
   ```bash
   cp .env.example .env
   # Edit .env with your settings
   ```

3. **Start Docker containers**
   ```bash
   docker-compose up -d
   ```

4. **Install dependencies**
   ```bash
   docker-compose exec app composer install
   ```

5. **Run migrations**
   ```bash
   docker-compose exec app php artisan migrate
   ```

6. **Generate Swagger documentation**
   ```bash
   docker-compose exec app php artisan l5-swagger:generate
   ```

7. **Access the application**
   - API: http://localhost:8000/api
   - API Documentation: http://localhost:8000/api/documentation
   - Prometheus: http://localhost:9090
   - Grafana: http://localhost:3000

## Development Roadmap

### ✅ Completed Stages

- **Stage 0**: GitHub repository and CI/CD setup
- **Stage 1**: Docker environment with PHP, PostgreSQL, Redis, RabbitMQ, Prometheus, Grafana
- **Stage 2**: Database schema design and migrations (users, projects, tasks, skills)
- **Stage 3**: CRUD operations and REST API
  - ✅ Controllers for Users, Tasks, Projects
  - ✅ FormRequest validation classes
  - ✅ API Resource classes
  - ✅ Swagger/OpenAPI documentation
  - ⏳ Redis caching (in progress)

### 🚧 Upcoming Stages

- **Stage 4**: Authentication and RBAC
  - JWT/Sanctum authentication
  - Role-based access control (Admin, Manager, User)
  - Policies and Guards

- **Stage 5**: Service Layer and Architectural Patterns
  - Service classes (TaskService, UserService, ProjectService)
  - Repository pattern implementation
  - Domain events and observers

- **Stage 6**: Intelligent Task Assignment (Go Microservice)
  - Go service for task distribution algorithms
  - Scoring based on skills, workload, priorities
  - Integration with Laravel via message queue

- **Stage 7**: Queues and Asynchrony
  - RabbitMQ integration
  - Dead Letter Queue (DLQ) implementation
  - Retry and backoff strategies

- **Stage 8**: Frontend (React + TypeScript)
  - SPA with project and task management
  - Real-time updates via WebSocket
  - Kanban board with drag & drop

- **Stage 9**: Testing
  - Unit tests for services and repositories
  - Integration tests with test database
  - Mock services for cross-service testing

- **Stage 10**: Production Deployment
  - Docker image optimization
  - CI/CD pipeline completion
  - Prometheus exporters and Grafana dashboards
  - Production monitoring setup

## API Documentation

Full API documentation is available via Swagger UI at:
http://localhost:8000/api/documentation

The API follows RESTful conventions and includes:
- User management endpoints
- Project CRUD operations
- Task management with assignment capabilities
- Workload tracking

## Project Goals

This project serves as a comprehensive learning resource covering:

- **Backend Development**: Laravel best practices, clean architecture, DDD patterns
- **Microservices**: Service decomposition, inter-service communication
- **Event-Driven Architecture**: Message queues, domain events, eventual consistency
- **Performance**: Caching strategies, database optimization, async processing
- **DevOps**: Docker, CI/CD, monitoring, observability
- **Frontend**: Modern React development with TypeScript
- **Testing**: Unit, integration, and E2E testing strategies

## Contributing

This is a learning project. Contributions, suggestions, and improvements are welcome!

## License

MIT License - feel free to use this project for learning purposes.
