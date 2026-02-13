import http from 'k6/http';
import { check } from 'k6';

export const options = {
    vus: 100, // 100 virtual users
    iterations: 1000, // Total 1000 concurrent requests
};

let requestCounter = 0;

export default function () {
    requestCounter++;
    const currentRequest = requestCounter;
    
    const pollSlug = 'poll-load-test-i6ayt';
    const url = `http://poll-management-system.test/poll-vote-test/${pollSlug}`;
    
    // sent to two options for the poll
    const optionIds = [5411, 5412];

    const randomOptionId = optionIds[Math.floor(Math.random() * optionIds.length)];
    
    const payload = JSON.stringify({
        option_id: randomOptionId,
    });
    
    const params = {
        headers: {
            'Content-Type': 'application/json',
            'Accept': 'application/json',
        },
    };
    
    let response;
    try {
        response = http.post(url, payload, params);
    } catch (e) {
        console.log(`Request #${currentRequest} | FAIL | Error: ${e}`);
        return;
    }
    
    const responseTime = Math.round(response.timings.duration);
    const isSuccess = response.status === 200 || response.status === 201;
    
    let responseBody;
    try {
        responseBody = JSON.parse(response.body);
    } catch {
        responseBody = response.body;
    }
    
    // Display in terminal
    console.log(
        `Request #${currentRequest} | ` +
        `Status: ${response.status} | ` +
        `${isSuccess ? 'SUCCESS' : 'FAIL'} | ` +
        `Time: ${responseTime}ms | ` +
        `Response: ${JSON.stringify(responseBody)}`
    );
    
    check(response, {
        'status is 200 or 201': (r) => r.status === 200 || r.status === 201,
        'has success message': (r) => {
            try {
                const body = JSON.parse(r.body);
                return body.success !== undefined;
            } catch {
                return false;
            }
        },
    });
}

export function handleSummary(data) {
    const totalRequests = data.metrics.iterations.values.count;
    const successfulChecks = data.metrics.checks ? data.metrics.checks.values.passes : 0;
    const failedChecks = data.metrics.checks ? data.metrics.checks.values.fails : 0;
    const avgTime = Math.round(data.metrics.http_req_duration.values.avg);
    const minTime = Math.round(data.metrics.http_req_duration.values.min);
    const maxTime = Math.round(data.metrics.http_req_duration.values.max);
    
    const summary = `
╔════════════════════════════════════════════════════════════╗
║              K6 LOAD TEST SUMMARY                          ║
╠════════════════════════════════════════════════════════════╣
║  Total Requests:          ${String(totalRequests).padStart(4)}                         ║
║  Successful Checks:       ${String(successfulChecks).padStart(4)}                         ║
║  Failed Checks:           ${String(failedChecks).padStart(4)}                         ║
║  Success Rate:            ${String(Math.round((successfulChecks / (successfulChecks + failedChecks)) * 100)).padStart(3)}%                        ║
║                                                            ║
║  Response Times:                                           ║
║    Min:  ${String(minTime).padStart(5)}ms                  ║
║    Avg:  ${String(avgTime).padStart(5)}ms                  ║
║    Max:  ${String(maxTime).padStart(5)}ms                  ║
╚════════════════════════════════════════════════════════════╝
`;
    
    return {
        'stdout': summary,
    };
}
