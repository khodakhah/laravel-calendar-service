# Laravel Calendar Service

This is a CRUD service for managing calendars, events, and working times.

> [!WARNING]
> This project is still in the development phase and is not ready for production use yet.

## What to expect

- **Race Condition (Double Bookings)** - Prevent overlapping reservations when multiple requests try to book the same slot at once.
- **Time-Zone Nightmare** - Keep stored times consistent while presenting correct local times across different regions.
- **Code Bloat vs. Microservice Isolation** - Balance a simple internal design with clear boundaries for future extraction or scaling.
- **Routine rules** - Support recurring availability and scheduling rules without making everyday cases hard to manage.

## Goal

The primary goal is to save the time of other devs for calendar-related applications without investing a lot of time in calendar principles.
It should be easy to install (especially with Docker) so that anyone can install and evaluate it in a few minutes.

## Testing

Tests are organized by the scope and dependencies they exercise. Put each test in the
smallest applicable suite to keep the feedback loop fast and the test's intent clear.

| Directory | Purpose | Database |
| --- | --- | --- |
| `tests/Unit` | Tests a single class or small piece of logic in isolation. Dependencies should be mocked or faked. | No |
| `tests/Integration` | Tests collaboration between application modules, such as services, events, queues, or external-client fakes. Use this when the behavior is broader than a unit test but does not require persistence or an HTTP endpoint. | No |
| `tests/Feature` | Tests externally visible behavior, including HTTP endpoints and workflows that persist or query data. | Yes, when the scenario requires it |

All suites use Pest. Integration and Feature tests boot the Laravel application; only
Feature tests should exercise the database.

## Code Quality

Run the style check with `composer lint` and Laravel-aware static analysis with
`composer analyse`.
