package main

import (
	"context"
	"encoding/json"
	"log"
	"net/http"
	"os"
	"os/signal"
	"syscall"
	"time"

	"scheduler/internal/db"
	"scheduler/internal/solver"
)

func main() {
	port := getEnv("HTTP_PORT", "8081")
	dbURL := getEnv("DATABASE_URL", "host=localhost user=postgres password=secret dbname=scheduler port=5432 sslmode=disable")

	database, err := db.NewPostgresDB(dbURL)
	if err != nil {
		log.Fatalf("Failed to connect to database: %v", err)
	}
	defer database.Close()

	scheduler := solver.NewScheduler(database)

	mux := http.NewServeMux()
	mux.HandleFunc("/health", func(w http.ResponseWriter, r *http.Request) {
		json.NewEncoder(w).Encode(map[string]string{"status": "ok"})
	})

	mux.HandleFunc("/api/v1/generate", scheduler.HandleGenerate)

	httpServer := &http.Server{
		Addr:    ":" + port,
		Handler: mux,
	}

	go func() {
		log.Printf("Solver HTTP server starting on port %s", port)
		if err := httpServer.ListenAndServe(); err != nil && err != http.ErrServerClosed {
			log.Fatalf("Failed to serve HTTP: %v", err)
		}
	}()

	quit := make(chan os.Signal, 1)
	signal.Notify(quit, syscall.SIGINT, syscall.SIGTERM)
	<-quit

	log.Println("Shutting down server...")

	ctx, cancel := context.WithTimeout(context.Background(), 5*time.Second)
	defer cancel()
	if err := httpServer.Shutdown(ctx); err != nil {
		log.Fatalf("Server forced to shutdown: %v", err)
	}

	log.Println("Server exited")
}

func getEnv(key, defaultValue string) string {
	if value, exists := os.LookupEnv(key); exists {
		return value
	}
	return defaultValue
}
