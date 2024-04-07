# State machine test task
State Machine for the Ticket object:
You need to develop a Symfony application that uses the Symfony Workflow component to manage ticket states. 
Create a "Ticket" entity with several possible states (for example, "Registered", "Paid", "Cancelled", etc.) and transitions between them.
Add logic to transitions that might include creating log entries or sending notifications.

## Important details about the implementation

1. A lot of the details are simplified on purpose , since I consider this task as kind of simple PoC and I would need more time to beautify it
2. For example in real world I would rather have ticket states as an integer instead of varchar
3. I would use another directory structure if we are talking about DDD
4. I would use some DTOs
5. I would cover each important point of system with test
6. logs covering, and outputs would be improved
7. To be continued...

## Setup
1.Run `docker compose build --no-cache` to build fresh images
2.Run `docker compose up --pull always -d --wait` to start the project
3. Run the `symfony console app:ticket-state-machine` to test the state machine
4. Run the phpunit tests
