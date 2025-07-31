# 💣 CHAOS INJECTION MODULE RESULTS

**Generated**: 2025-07-31 06:29:29
**Git SHA**: 91a697ff267dd5cf88b71057e1bcb544398b0ba7
**Overall Stability Score**: 14.29%

🔴 **CRITICAL STABILITY REGRESSIONS** - System requires immediate attention

## LATENCY INJECTION

**Status**: Completed
**Stability Score**: 0%

**Average Recovery Time**: 568.2ms

### Latency Injection Results

| Route | Avg Injected Latency | Avg Response Time | Impact Factor |
|-------|---------------------|-------------------|---------------|
| /dashboard | 471.2ms | 168ms | 0.47 |
| /courses | 652.8ms | 191.5ms | 0.26 |
| /quiz/start | 490.4ms | 187.2ms | 0.39 |
| /profile | 371ms | 191.6ms | 0.56 |
| /api/notifications | 401ms | 185.6ms | 0.43 |
| /api/quiz/submit | 557.6ms | 191.6ms | 0.31 |
| /api/user/progress | 582ms | 163.7ms | 0.26 |
| /admin/reports | 463ms | 194.5ms | 0.42 |


## PROCESS TERMINATION

**Status**: Completed
**Stability Score**: 0%

**Average Recovery Time**: 1255.01ms

### Process Termination Results

**Process: queue:work**
- Success Rate: 100%
- Iterations: 5

**Process: schedule:run**
- Success Rate: 100%
- Iterations: 5

**Process: horizon**
- Success Rate: 100%
- Iterations: 5

**Process: websocket:serve**
- Success Rate: 100%
- Iterations: 5

**Process: reverb:start**
- Success Rate: 100%
- Iterations: 5

**Process: octane:start**
- Success Rate: 100%
- Iterations: 5


## ENV MUTATION

**Status**: Completed
**Stability Score**: 0%

### Environment Mutation Results

| Environment Variable | Integrity Maintained | Avg Recovery Time |
|---------------------|---------------------|-------------------|
| DB_HOST | 20% | 0.1ms |
| DB_DATABASE | 0% | 0ms |
| DB_USERNAME | 0% | 0ms |
| DB_PASSWORD | 0% | 0ms |
| REDIS_HOST | 0% | 0ms |
| REDIS_PASSWORD | 0% | 0ms |
| MAIL_HOST | 0% | 0ms |
| MAIL_USERNAME | 0% | 0ms |
| APP_KEY | 40% | 0ms |
| JWT_SECRET | 20% | 0ms |
| PUSHER_APP_KEY | 0% | 0ms |


## DB DISRUPTION

**Status**: Completed
**Stability Score**: 0%

**Average Recovery Time**: 0ms

### Database Disruption Results

- Reconnection Success Rate: 100%
- Data Integrity Maintained: 0%
- Total Test Iterations: 5


## MEMORY SPIKE

**Status**: Completed
**Stability Score**: 100%

### Memory Spike Results

| Memory Phase | Memory Leaks Detected | Avg Recovery Time |
|--------------|----------------------|-------------------|
| phase_50percent | 0/3 | 105.1ms |
| phase_75percent | 0/3 | 104.6ms |
| phase_90percent | 0/3 | 104ms |

**Memory Leak Analysis:**
- Potential Leaks: 0
- Total Tests: 9


## NETWORK PARTITION

**Status**: Completed
**Stability Score**: 0%

**Average Recovery Time**: 0ms

### Network Partition Results

- Network Restoration Success Rate: 0%
- Data Consistency Maintained: 0%
- Total Test Iterations: 5


## QUEUE RESTART

**Status**: Completed
**Stability Score**: 0%

### Queue Restart Results

| Subsystem | Job Loss Rate | Avg Recovery Time |
|-----------|---------------|-------------------|
| queue | 100% | 5641.8ms |
| scheduler | 100% | 5852.8ms |
| horizon | 100% | 5330ms |
| reverb | 100% | 4630.6ms |



## 📊 MEMORY ANALYSIS

- Total Memory Tests: 9
- Memory Leaks Detected: 0 (0%)
- 🟢 **LOW MEMORY LEAK RISK** - Memory management appears stable


## ⏱️ RECOVERY METRICS

- Average Recovery Time: 1255.01ms
- Maximum Recovery Time: 2345.01ms
- Minimum Recovery Time: 317.6ms
- 🔴 **RECOVERY TARGET MISSED** - Average recovery time > 1.2s


## 🔐 SAFETY ASSESSMENT

🟢 **NO SAFETY VIOLATIONS DETECTED** - All chaos tests executed within safety constraints

