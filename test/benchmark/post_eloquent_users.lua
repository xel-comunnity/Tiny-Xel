-- wrk load test script for POST /api/v1/eloquent-users
--
-- Usage:
--   wrk -t4 -c100 -d30s -s test/benchmark/post_eloquent_users.lua \
--       http://127.0.0.1:9501/api/v1/eloquent-users
--
--   -t  number of wrk threads
--   -c  number of concurrent connections
--   -d  test duration (e.g. 30s, 1m)
--
-- The endpoint validates name/email and requires email to be unique
-- (see test/src/Http/Service/EloquentDemo.php), so every request body
-- below uses a per-thread, per-request-counter email to guarantee
-- uniqueness across the whole run - reusing the same email on every
-- request would make every request after the first fail validation
-- with a 422, which is not what you want to be measuring.

wrk.method = "POST"
wrk.headers["Content-Type"] = "application/json"

-- ? wrk runs each thread as its own independent Lua VM - none of them
-- ? share state, which is why passing values in (thread:set, before the
-- ? run) and back out (thread:get, in done() after it) needs this
-- ? explicit thread handle table instead of a plain shared variable.
local threads = {}

-- ? runs once per thread, in the main process, before the benchmark
-- ? starts - assigns each thread a stable numeric id so emails never
-- ? collide between threads.
function setup(thread)
    thread:set("id", #threads + 1)
    table.insert(threads, thread)
end

-- ? runs once per thread, in that thread's own Lua state, right before
-- ? it starts firing requests.
function init(args)
    request_count = 0
    status_counts = {}
    math.randomseed(os.time() + id)
end

function request()
    request_count = request_count + 1

    local email = string.format(
        "wrk_t%d_r%d_%d@example.com",
        id,
        request_count,
        math.random(0, 1000000)
    )

    local body = string.format(
        '{"name":"wrk load test","email":"%s"}',
        email
    )

    return wrk.format(nil, nil, nil, body)
end

function response(status, headers, body)
    status_counts[status] = (status_counts[status] or 0) + 1
end

-- ? runs once, back in the main process, after every thread has
-- ? finished - pulls each thread's local status_counts back via
-- ? thread:get() and aggregates them, since a pure requests/sec number
-- ? hides a wall of failed (e.g. 422/500) requests otherwise.
function done(summary, latency, requests)
    local aggregate = {}

    for _, thread in ipairs(threads) do
        local counts = thread:get("status_counts")
        if counts then
            for status, count in pairs(counts) do
                aggregate[status] = (aggregate[status] or 0) + count
            end
        end
    end

    io.write("\n------------------------------\n")
    io.write("Response status breakdown:\n")
    for status, count in pairs(aggregate) do
        io.write(string.format("  %s: %d\n", tostring(status), count))
    end
    io.write("------------------------------\n")
end
