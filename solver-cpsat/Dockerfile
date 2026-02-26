FROM python:3.11-slim

WORKDIR /app

COPY requirements.txt .
RUN pip install --no-cache-dir -r requirements.txt

COPY solver/ ./solver/

EXPOSE 8082

CMD ["uvicorn", "solver.main:app", "--host", "0.0.0.0", "--port", "8082"]
