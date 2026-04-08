# Supabase Setup Guide for Frontend Data Submission

This guide will help you set up your Supabase project to accept data submissions from your frontend (e.g., via a WordPress plugin form).

## 1. Create a Table in Supabase

You can create a table using the Supabase Dashboard or SQL editor.

### Using the Dashboard

1. Go to your [Supabase project dashboard](https://app.supabase.com/).
2. Navigate to **Table Editor** > **New Table**.
3. Enter a table name (e.g., `contacts`).
4. Add columns as needed, for example:
   - `id` (UUID, Primary Key, default: `gen_random_uuid()`)
   - `first_name` (text)
   - `last_name` (text)
   - `email` (text)
   - `phone` (text)
   - `age` (integer)
   - `date_of_birth` (timestamp)
   - `contact` (jsonb) — for nested contact info (first_name, last_name, sex)
   - `hobbies` (text[] array)
   - `food` (text[] array)
   - `gender` (text)
   - `created_at` (timestamp, default: `now()`)
   - `updated_at` (timestamp, default: `now()`)
5. Click **Save** to create the table.

> **Note:**
> - Use **jsonb** for objects (e.g., `contact`).
> - Use **text[]** for arrays (e.g., `hobbies`, `food`).

### Using SQL

```sql
create table public.contacts (
  id uuid primary key default gen_random_uuid(),
  first_name text,
  last_name text,
  email text,
  phone text,
  age integer,
  date_of_birth timestamp,
  contact jsonb,
  hobbies text[],
  food text[],
  gender text,
  created_at timestamp with time zone default now(),
  updated_at timestamp with time zone default now()
);
```

## 2. Enable Row Level Security (RLS)

Supabase enables RLS by default for new tables. If not, enable it:

```sql
alter table public.contacts enable row level security;
```

## 3. Add an Insert Policy

To allow your frontend to insert data, add a policy. For public forms, you might allow all inserts (not recommended for sensitive data). For authenticated users, restrict by user.

### Example: Allow All Inserts (for testing only)

```sql
create policy "Allow insert for all" on public.contacts
  for insert
  with check (true);
```

### Example: Allow All Updates (for upsert, testing only)

```sql
create policy "Allow update for all" on public.contacts
  for update
  using (true);
```

### Example: Allow All Selects (for testing/public access)

```sql
create policy "Allow select for all" on public.contacts
  for select
  using (true);
```

> **Note:**
> Upsert operations require SELECT, INSERT and UPDATE permissions. For production, restrict these policies to authenticated users or add more conditions.

### Example: Allow Inserts Only for Authenticated Users

If you have authentication, use:

```sql
create policy "Allow insert for authenticated" on public.contacts
  for insert
  using (auth.uid() is not null);
```

## 4. Best Practices & Security Tips

- **Never allow unrestricted access in production.** Use authenticated policies whenever possible.
- Validate and sanitize all data on the backend.
- Regularly review your RLS policies.
- Use Supabase's dashboard to monitor table activity and security.

## 5. Example Frontend Code

```ts
import { initSupabase } from './supabase-core'

const supabase = initSupabase()
const tableName = 'contacts' // or get from form/table field
const { error } = await supabase.from(tableName).insert([data])
if (error) {
  errorLog('Error inserting data into Supabase:', error.message)
} else {
  debugLog('Successfully inserted data into Supabase')
}
```

---

For more details, see the [Supabase documentation](https://supabase.com/docs/guides/auth/row-level-security).
