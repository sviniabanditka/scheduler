import asyncio
import logging

from fastapi import FastAPI

from .cpsat_solver import solve
from .models import SolveRequest, SolveResponse

logging.basicConfig(level=logging.INFO)
logger = logging.getLogger(__name__)

app = FastAPI(title="CP-SAT Schedule Solver")


@app.get("/health")
async def health():
    return {"status": "ok"}


@app.post("/api/v1/solve", response_model=SolveResponse)
async def solve_schedule(request: SolveRequest):
    logger.info(
        "Received solve request: %d activities, %d slots, %d rooms, timeout=%ds",
        len(request.activities),
        len(request.time_slots),
        len(request.rooms),
        request.timeout_seconds,
    )

    # Run CPU-bound solver in thread pool
    result = await asyncio.get_event_loop().run_in_executor(None, lambda: solve(request))

    logger.info(
        "Solve complete: status=%s, %d assignments, %d violations, %dms",
        result.status,
        len(result.assignments),
        len(result.violations),
        result.solve_time_ms,
    )

    return result
