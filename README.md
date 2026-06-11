# chat-api
A personal experiment building a production-style REST API from scratch — groups, users, messaging, and a full integration test suite.

I wanted to experiment with a new language. As I had never used PHP before, I consulted documentation and AI to learn best practices and proper idiom.

I've made this a public repository to demonstrate the kind of work I am capable of in a language I have little experience with.

## Design Thoughts

This is an intentionally scoped project, so it lacks some things one would expect to see in a real world application.

Having said that, what follows are notes concerning implementation decisions and ideas for further discussion.

For the API route contract and endpoint reference, please see [ROUTING](ROUTING.md).

### Naming Conventions

1. The SQL schema (SQLite) uses traditional Snake case.
2. The PHP code uses Pascal and Camel case per convention.
3. SQL results are aliased directly (e.g., `user.id AS userId`) in each prepared statement. While this does provide a consistent, JSON aware result without introducing overhead, it does couple the SQL to the JSON output which is a tradeoff. If this were a real world application, I might have maintained strict Snake case in the SQL and then transformed names as necessary in the Repository classes. Even so, given how `id` is used as the primary key in several tables, it is often necessary to use the `AS` clause to transform it which still introduces a certain coupling.

### Database (SQLite)

1. All group IDs are expected to be of type UUID7. This was done to make them difficult to discover yet work well as a primary key. Please see [RFC 9562](https://www.rfc-editor.org/rfc/rfc9562.html) for details.
2. All user IDs are expected to be of type UUID4. This was done to prevent them from being easily discovered. They are represented as TEXT but one might consider using a 16 byte binary value instead.
3. All message IDs are expected to be a monotonically increasing integer. At the moment the message ID is not exposed in a meaningful way, but this may be worth further consideration.
4. Please see the other notes expressed directly in the [schema](database/schema.sql).
5. Rather than pull out the SQLite specific error codes I opted to use the generic ODBC codes which did not give me enough granularity. Consequently, the errors returned are not always accurate. This would certainly need to be addressed for a real world application. Please see a corresponding note in the [Observability](#observability) section of [Future Work](#future-work).

### Security

1. All features are currently public and no real security is enforced. Authentication would properly be done using a JWT and a validation mechanism. Authorization is also out of scope here, but a number of areas to consider are called out in the main app [script](src/app.php).
2. Various performance, security and other enhancements are noted in comments throughout the repo.
3. In general, all publicly facing routes should be put behind a rate limiter. This also has a performance component.

## Future Work

Many projects are both too large and at the same time, incomplete. This one is no exception. The following items should be considered to turn this into something that would face the real world.

### Observability

1. Currently no telemetry or logging is performed other than in exception handlers, and that to the console.
2. OTEL might be leveraged here to record spans and traces in Datadog.
3. Logging could be performed by reporting to a robust logging platform like Splunk.
4. Various analytics might be sent to Big Query.
5. Datadog could be used to record SLIs and manage SLOs.
6. Exception handling can be expanded to pull SQLite specific errors to return better HTTP status codes.
