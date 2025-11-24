package main

import (
	"context"
	"database/sql"
	"fmt"
	"os"
	"os/signal"
	"syscall"
	"task-optimizer/internal/application"
	"task-optimizer/internal/domain"
	"task-optimizer/internal/infrastructure/config"
	"task-optimizer/internal/infrastructure/messaging/rabbitmq"
	"task-optimizer/internal/infrastructure/repository/postgres"
	"task-optimizer/internal/interfaces/consumer"
	"task-optimizer/pkg/logger"

	_ "github.com/lib/pq"
	"go.uber.org/zap"
)

func main() {
	cfg, err := config.Load()
	if err != nil {
		_, _ = fmt.Fprintf(os.Stderr, "Failed to load config: %v\n", err)
		os.Exit(1)
	}

	log, err := logger.NewLogger(cfg.Service.LogLevel)
	if err != nil {
		_, _ = fmt.Fprintf(os.Stderr, "Failed to initialize logger: %v\n", err)
		os.Exit(1)
	}
	defer func() { _ = log.Sync() }()

	log.Info("Starting Task Optimizer Service",
		zap.String("log_level", cfg.Service.LogLevel),
		zap.Int("worker_count", cfg.Service.WorkerCount),
	)

	db, err := connectDatabase(cfg.Database, log)
	if err != nil {
		log.Fatal("Failed to connect to database", zap.Error(err))
	}
	defer func() { _ = db.Close() }()

	rabbitConn, err := rabbitmq.NewConnection(cfg.RabbitMQ.URL, log)
	if err != nil {
		log.Fatal("Failed to connect to RabbitMQ", zap.Error(err))
	}
	defer func() { _ = rabbitConn.Close() }()

	if err := setupRabbitMQ(rabbitConn, cfg.RabbitMQ, log); err != nil {
		log.Fatal("Failed to setup RabbitMQ", zap.Error(err))
	}

	userRepo := postgres.NewUserRepository(db)
	publisher := rabbitmq.NewPublisher(rabbitConn, cfg.RabbitMQ.Exchange, log)
	optimizerService := domain.NewOptimizerService(userRepo)

	assignTaskUC := application.NewAssignTaskUseCase(
		optimizerService,
		userRepo,
		publisher,
		log,
	)

	taskHandler := consumer.NewTaskEventHandler(assignTaskUC, log)
	taskConsumer := rabbitmq.NewConsumer(
		rabbitConn,
		taskHandler.HandleTaskCreated,
		log,
	)

	ctx, cancel := context.WithCancel(context.Background())
	defer cancel()

	if err := taskConsumer.StartConsuming(ctx, cfg.RabbitMQ.QueueTaskCreated); err != nil {
		log.Fatal("Failed to start consuming", zap.Error(err))
	}

	log.Info("Task Optimizer Service is running. Press Ctrl+C to exit.")

	waitForShutdown(cancel, log)

	log.Info("Task Optimizer Service stopped")
}

// connectDatabase establishes a connection to PostgreSQL
func connectDatabase(cfg config.DatabaseConfig, log *zap.Logger) (*sql.DB, error) {
	log.Info("Connecting to PostgreSQL",
		zap.String("host", cfg.Host),
		zap.String("port", cfg.Port),
		zap.String("database", cfg.DBName),
	)

	db, err := sql.Open("postgres", cfg.GetDSN())
	if err != nil {
		return nil, fmt.Errorf("failed to open database: %w", err)
	}

	if err := db.Ping(); err != nil {
		return nil, fmt.Errorf("failed to ping database: %w", err)
	}

	db.SetMaxOpenConns(25)
	db.SetMaxIdleConns(5)

	log.Info("Connected to PostgreSQL successfully")

	return db, nil
}

// setupRabbitMQ declares exchanges and queues
func setupRabbitMQ(conn *rabbitmq.Connection, cfg config.RabbitMQConfig, log *zap.Logger) error {
	log.Info("Setting up RabbitMQ exchanges and queues")

	if err := conn.DeclareExchange(cfg.Exchange); err != nil {
		return fmt.Errorf("failed to declare exchange: %w", err)
	}

	if err := conn.DeclareQueue(cfg.QueueTaskCreated, cfg.Exchange, cfg.QueueTaskCreated); err != nil {
		return fmt.Errorf("failed to declare task.created queue: %w", err)
	}

	if err := conn.DeclareQueue(cfg.QueueTaskAssigned, cfg.Exchange, cfg.QueueTaskAssigned); err != nil {
		return fmt.Errorf("failed to declare task.assigned queue: %w", err)
	}

	log.Info("RabbitMQ setup completed")
	return nil
}

// waitForShutdown waits for interrupt signal and triggers graceful shutdown
func waitForShutdown(cancel context.CancelFunc, log *zap.Logger) {
	sigChan := make(chan os.Signal, 1)
	signal.Notify(sigChan, os.Interrupt, syscall.SIGTERM)

	<-sigChan

	log.Info("Shutdown signal received, stopping service...")
	cancel()
}
