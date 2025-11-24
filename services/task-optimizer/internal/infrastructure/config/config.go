package config

import (
	"fmt"
	"os"

	"github.com/joho/godotenv"
)

type Config struct {
	Database DatabaseConfig
	RabbitMQ RabbitMQConfig
	Service  ServiceConfig
}

type DatabaseConfig struct {
	Host     string
	Port     string
	User     string
	Password string
	DBName   string
	SSLMode  string
}

type RabbitMQConfig struct {
	URL               string
	Exchange          string
	QueueTaskCreated  string
	QueueTaskAssigned string
}

type ServiceConfig struct {
	LogLevel    string
	WorkerCount int
}

// Load loads configuration from environment variables
func Load() (*Config, error) {
	_ = godotenv.Load()

	cfg := &Config{
		Database: DatabaseConfig{
			Host:     getEnv("DB_HOST", "postgres"),
			Port:     getEnv("DB_PORT", "5432"),
			User:     getEnv("DB_USER", "smart_task_user"),
			Password: getEnv("DB_PASSWORD", "secret"),
			DBName:   getEnv("DB_NAME", "smart_task_db"),
			SSLMode:  getEnv("DB_SSLMODE", "disable"),
		},
		RabbitMQ: RabbitMQConfig{
			URL:               getEnv("RABBITMQ_URL", "amqp://guest:guest@rabbitmq:5672/"),
			Exchange:          getEnv("RABBITMQ_EXCHANGE", "tasks"),
			QueueTaskCreated:  getEnv("RABBITMQ_QUEUE_TASK_CREATED", "task.created"),
			QueueTaskAssigned: getEnv("RABBITMQ_QUEUE_TASK_ASSIGNED", "task.assigned"),
		},
		Service: ServiceConfig{
			LogLevel:    getEnv("LOG_LEVEL", "info"),
			WorkerCount: getEnvInt("WORKER_COUNT", 5),
		},
	}

	return cfg, nil
}

// GetDSN returns PostgreSQL connection string
func (c *DatabaseConfig) GetDSN() string {
	return fmt.Sprintf(
		"host=%s port=%s user=%s password=%s dbname=%s sslmode=%s",
		c.Host, c.Port, c.User, c.Password, c.DBName, c.SSLMode,
	)
}

func getEnv(key, defaultValue string) string {
	if value := os.Getenv(key); value != "" {
		return value
	}
	return defaultValue
}

func getEnvInt(key string, defaultValue int) int {
	if value := os.Getenv(key); value != "" {
		var result int
		if _, err := fmt.Sscanf(value, "%d", &result); err == nil {
			return result
		}
	}
	return defaultValue
}
