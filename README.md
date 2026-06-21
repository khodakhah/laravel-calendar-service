# Laravel Calendar Service

This is a CRUD service for managing calendars, events, and working times.

## What to expect

- **Race Condition (Double Bookings)** - Prevent overlapping reservations when multiple requests try to book the same slot at once.
- **Time-Zone Nightmare** - Keep stored times consistent while presenting correct local times across different regions.
- **Code Bloat vs. Microservice Isolation** - Balance a simple internal design with clear boundaries for future extraction or scaling.
- **Routine rules** - Support recurring availability and scheduling rules without making everyday cases hard to manage.

## Goal

The primary goal is to save the time of other devs for calendar-related applications without investing a lot of time in calendar principles.
It should be easy to install (especially with Docker) so that anyone can install and evaluate it in a few minutes.
