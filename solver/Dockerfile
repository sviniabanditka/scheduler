FROM golang:1.21-alpine AS builder

WORKDIR /app

RUN apk add --no-cache git protobuf

COPY solver/go.mod solver/go.sum ./
RUN go mod download

COPY solver/ ./

RUN CGO_ENABLED=0 GOOS=linux go build -o /solver ./cmd/server

FROM alpine:3.19

RUN apk add --no-cache ca-certificates tzdata

WORKDIR /app

COPY --from=builder /solver .

ENV GRPC_PORT=50051
ENV HTTP_PORT=8081
ENV DATABASE_URL=host=postgres user=postgres password=secret dbname=scheduler port=5432 sslmode=disable

EXPOSE 50051 8081

CMD ["./solver"]
