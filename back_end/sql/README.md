# README


## `schema.sql`

The schema used by the source data

`schema.sql` must not contain comments as it would be executed inline.

## `rows.sql`

The query to execute to export data in the format understood by the frontend

`rows.sql` must not contain comments and must not end with a semicolon as it
would be prepended to the `INTO OUTFILE...` statement and executed inline.
