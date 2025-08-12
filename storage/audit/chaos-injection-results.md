# 💣 CHAOS INJECTION MODULE RESULTS

**Generated**: 2025-08-06 12:28:17
**Git SHA**: 71e8a50457bfdec5c40b909d1ad0972208358ddb
**Overall Stability Score**: 68.57%

🔴 **CRITICAL STABILITY REGRESSIONS** - System requires immediate attention

## LATENCY INJECTION

**Status**: Completed
**Stability Score**: 100%

**Average Recovery Time**: 633.97ms

### Latency Injection Results

| Route | Avg Injected Latency | Avg Response Time | Impact Factor |
|-------|---------------------|-------------------|---------------|
| /dashboard | 274.4ms | 171.3ms | 0.49 |
| /courses | 299.8ms | 198.1ms | 0.61 |
| /quiz/start | 203.2ms | 181.8ms | 0.7 |
| /profile | 272.6ms | 163.7ms | 0.57 |
| /api/notifications | 369ms | 172.3ms | 0.48 |
| /api/quiz/submit | 346.2ms | 186.2ms | 0.47 |
| /api/user/progress | 306ms | 169.4ms | 0.49 |
| /admin/reports | 177.2ms | 199.8ms | 0.87 |


## PROCESS TERMINATION

**Status**: Completed
**Stability Score**: 100%

**Average Recovery Time**: 366.06ms

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


## ENV MUTATION

**Status**: Completed
**Stability Score**: 100%

### Environment Mutation Results

| Environment Variable | Integrity Maintained | Avg Recovery Time |
|---------------------|---------------------|-------------------|
| DB_HOST | 0% | 0ms |
| REDIS_HOST | 0% | 0ms |
| MAIL_HOST | 0% | 0ms |
| APP_KEY | 20% | 0ms |


## DB DISRUPTION

**Status**: Completed
**Stability Score**: 80%

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
| phase_25percent | 0/3 | 105.2ms |
| phase_50percent | 0/3 | 101.3ms |
| phase_75percent | 0/3 | 102ms |

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
| queue | 100% | 1641.3ms |
| scheduler | 100% | 1488.1ms |



## 📊 MEMORY ANALYSIS

- Total Memory Tests: 9
- Memory Leaks Detected: 0 (0%)
- 🟢 **LOW MEMORY LEAK RISK** - Memory management appears stable


## ⏱️ RECOVERY METRICS

- Average Recovery Time: 366.06ms
- Maximum Recovery Time: 553.58ms
- Minimum Recovery Time: 162.69ms
- 🟢 **RECOVERY TARGET MET** - Average recovery time ≤ 1.2s


## 🔐 SAFETY ASSESSMENT

🟢 **NO SAFETY VIOLATIONS DETECTED** - All chaos tests executed within safety constraints

