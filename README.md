# Job Board API with Advanced Filtering

A Laravel 11 application that provides a RESTful API for managing job listings with complex filtering capabilities similar to Airtable. The application handles different job types with varying attributes using Entity-Attribute-Value (EAV) design patterns alongside traditional relational database models.

## Table of Contents

- [Requirements](#requirements)
- [Installation](#installation)
- [Database Structure](#database-structure)
- [API Endpoints](#api-endpoints)
- [Filtering Syntax](#filtering-syntax)
  - [Basic Field Filtering](#basic-field-filtering)
  - [Relationship Filtering](#relationship-filtering)
  - [EAV Attribute Filtering](#eav-attribute-filtering)
  - [Logical Operators](#logical-operators)
  - [Filter Examples](#filter-examples)
- [Development Notes](#development-notes)

## Requirements

- PHP 8.2+
- Laravel 11
- MySQL 8.0+
- Docker (for Laravel Sail)

## Installation

1. Clone the repository:
   ```
   git clone https://github.com/yourusername/job-board-api.git
   cd job-board-api
   ```

2. Set up environment:
   ```
   cp .env.example .env
   ```

3. Start Docker containers with Laravel Sail:
   ```
   ./vendor/bin/sail up -d
   ```

4. Run migrations and seed the database:
   ```
   ./vendor/bin/sail artisan migrate --seed
   ```

## Database Structure

The database consists of the following main tables:

1. **job_listings**: Stores the core job listing data
2. **languages**: Programming languages required for jobs
3. **categories**: Job categories/departments
4. **locations**: Possible locations for jobs
5. **attributes**: Dynamic attributes metadata (EAV pattern)
6. **job_attribute_values**: Values for dynamic attributes (EAV pattern)

Pivot tables:
- **job_language**: Many-to-many between jobs and languages
- **job_category**: Many-to-many between jobs and categories
- **job_location**: Many-to-many between jobs and locations

## API Endpoints

### Get Jobs List with Filtering

```
GET /api/jobs
```

Query Parameters:
- `filter`: Filter expression (see Filtering Syntax below)
- `per_page`: Number of results per page (default: 10)
- `page`: Page number for pagination

Response:
```json
{
  "data": [
    {
      "id": 1,
      "title": "Job Title 1",
      "description": "Job description...",
      "company_name": "Company Name",
      "salary_min": "50000.00",
      "salary_max": "100000.00",
      "is_remote": true,
      "job_type": "full-time",
      "status": "published",
      "published_at": "2023-03-15T12:00:00.000000Z",
      "created_at": "2023-03-10T12:00:00.000000Z",
      "updated_at": "2023-03-10T12:00:00.000000Z",
      "languages": [
        {"id": 1, "name": "PHP"},
        {"id": 2, "name": "JavaScript"}
      ],
      "locations": [],
      "categories": [
        {"id": 1, "name": "Web Development"}
      ],
      "attributes": [
        {
          "id": 1,
          "name": "Years of Experience",
          "type": "number",
          "value": 5,
          "raw_value": "5"
        }
      ]
    }
  ],
  "meta": {
    "current_page": 1,
    "from": 1,
    "last_page": 5,
    "per_page": 10,
    "to": 10,
    "total": 50
  }
}
```

### Get Single Job

```
GET /api/jobs/{id}
```

Response:
```json
{
  "data": {
    "id": 1,
    "title": "Job Title 1",
    "description": "Job description...",
    "company_name": "Company Name",
    "salary_min": "50000.00",
    "salary_max": "100000.00",
    "is_remote": true,
    "job_type": "full-time",
    "status": "published",
    "published_at": "2023-03-15T12:00:00.000000Z",
    "created_at": "2023-03-10T12:00:00.000000Z",
    "updated_at": "2023-03-10T12:00:00.000000Z",
    "languages": [
      {"id": 1, "name": "PHP"},
      {"id": 2, "name": "JavaScript"}
    ],
    "locations": [],
    "categories": [
      {"id": 1, "name": "Web Development"}
    ],
    "attributes": [
      {
        "id": 1,
        "name": "Years of Experience",
        "type": "number",
        "value": 5,
        "raw_value": "5"
      }
    ]
  }
}
```

## Filtering Syntax

The API supports a powerful filtering syntax that allows for complex queries.

### Basic Field Filtering

You can filter by any field in the job_listings table using the following operators:

- Equality: `=`, `!=`
- Comparison: `>`, `<`, `>=`, `<=`
- Contains: `LIKE`
- Multiple values: `IN` (using parentheses)

Examples:
```
title = "Senior Developer"
company_name LIKE "tech"
salary_min >= 50000
job_type = (full-time, contract)
```

### Relationship Filtering

You can filter by relationships using the following operators:

- `HAS_ANY`: Job has any of the specified values in the relationship
- `IS_ANY`: Relationship matches any of the values (with special handling for locations)
- `EXISTS`: Relationship exists

Examples:
```
languages HAS_ANY (PHP, JavaScript)
locations IS_ANY (New York, Remote)
categories EXISTS
```

### EAV Attribute Filtering

You can filter by dynamic attributes using the prefix `attribute:` followed by the attribute name:

Examples:
```
attribute:Years of Experience >= 3
attribute:Requires Degree = true
attribute:Education Level = Bachelor
```

### Logical Operators

You can combine multiple conditions using logical operators:

- `AND`: All conditions must be true
- `OR`: At least one condition must be true

You can also use parentheses to group conditions.

Examples:
```
(job_type = full-time AND salary_min >= 50000) OR (job_type = contract AND salary_min >= 70000)
```

### Filter Examples

Here are some complete filter examples:

1. Find full-time jobs requiring PHP or JavaScript in New York or Remote locations:
```
job_type = full-time AND languages HAS_ANY (PHP, JavaScript) AND locations IS_ANY (New York, Remote)
```

2. Find senior developer positions with at least 5 years of experience and a salary above $100,000:
```
title LIKE "Senior" AND attribute:Years of Experience >= 5 AND salary_min >= 100000
```

3. Find remote contract jobs with advanced degrees:
```
is_remote = true AND job_type = contract AND attribute:Education Level = (Master, PhD)
```

4. Find published jobs from a specific company with required certifications:
```
status = published AND company_name = "Acme Inc" AND attribute:Requires Certification = true
```

## Development Notes

- Uses EAV pattern for flexible attributes
- Implements advanced filter parser for building dynamic Eloquent queries
- Optimized database schema with proper indexing
- Handles nested AND/OR conditions with parentheses grouping
