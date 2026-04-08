# Supabase Database Setup for SupaWP

This document provides the necessary SQL commands to set up the required table in your Supabase database for syncing user data with the SupaWP plugin.

## User Profile Table Setup

To allow SupaWP to save user information (like ID and email) when a user signs up via the plugin, you need a corresponding table in your Supabase database. The following SQL script creates this table, enables Row Level Security (RLS), and sets up the required policies.

**Instructions:**

1.  Navigate to your Supabase project dashboard.
2.  Go to the **SQL Editor** (usually under the Database section).
3.  Click on **New query**.
4.  Copy the entire SQL script below.
5.  **IMPORTANT:** Before running, replace all instances of `public.profiles` in the script with the actual table name you have configured (or intend to configure) in the SupaWP plugin settings (e.g., `public.users`).
6.  Run the query.

```sql
-- 1. Create the table to store user profiles
-- Replace 'public.profiles' with your actual table name if different.
CREATE TABLE public.profiles (
  id uuid NOT NULL PRIMARY KEY, -- Matches auth.users.id, Primary Key
  email text,                  -- User's email
  created_at timestamptz DEFAULT now(), -- Timestamp when the row is created
  updated_at timestamptz DEFAULT now()  -- Timestamp for updates (can be auto-updated later with triggers if needed)
);

-- 2. Add Foreign Key constraint linking to auth.users table
-- Ensures that the 'id' in your profiles table corresponds to a real user in Supabase Auth.
-- Replace 'public.profiles' with your actual table name.
ALTER TABLE public.profiles
  ADD CONSTRAINT fk_auth_users FOREIGN KEY (id) REFERENCES auth.users(id) ON DELETE CASCADE;
  -- ON DELETE CASCADE means if a user is deleted from auth.users, their corresponding row here is also deleted.

-- 3. Enable Row Level Security (RLS) on the table
-- IMPORTANT: This secures your table so that policies are enforced.
-- Replace 'public.profiles' with your actual table name.
ALTER TABLE public.profiles ENABLE ROW LEVEL SECURITY;

-- 4. Create the INSERT policy
-- Allows any authenticated user to insert a row *only if* the 'id' they are inserting
-- matches their own authenticated User ID (auth.uid()).
-- Replace 'public.profiles' with your actual table name.
CREATE POLICY "Allow authenticated users insert own profile"
ON public.profiles
FOR INSERT
TO authenticated
WITH CHECK (auth.uid() = id);

-- (Optional) 5. Create SELECT policy (if you need users to read their own profile from frontend)
-- Replace 'public.profiles' with your actual table name.
CREATE POLICY "Allow authenticated users read own profile"
ON public.profiles
FOR SELECT
TO authenticated
USING (auth.uid() = id);

```

**Explanation:**

- **Step 1:** Creates the table with essential columns (`id`, `email`, `created_at`, `updated_at`).
- **Step 2:** Links the table's `id` to the `auth.users` table for data integrity.
- **Step 3:** Enables Row Level Security, which is crucial for applying access rules.
- **Step 4:** Creates the necessary `INSERT` policy, allowing users to add their _own_ profile record upon signup.
- **Step 5 (Optional):** Provides a template for a `SELECT` policy if you need users to be able to fetch their own profile data later.

## Adding More Fields Later

If you need to store additional user information (e.g., first name, last name, avatar URL), you can add new columns to your profiles table at any time using the `ALTER TABLE` command.

**Instructions:**

1.  Decide on the new column names and their data types (e.g., `text` for names, `text` or `varchar` for URLs).
2.  Go to the Supabase **SQL Editor**.
3.  Run the `ALTER TABLE ... ADD COLUMN` command for each new field. **Remember to replace `public.profiles` with your actual table name.**

```sql
-- Example: Adding first_name and last_name columns
-- Replace 'public.profiles' with your actual table name.

ALTER TABLE public.profiles
ADD COLUMN first_name text,
ADD COLUMN last_name text;

-- Example: Adding an avatar_url column
-- ALTER TABLE public.profiles
-- ADD COLUMN avatar_url text;
```

**Important Considerations:**

- **RLS Policies for Updates:** If you want users to be able to **update** these new fields themselves (e.g., through a profile editing page), you will need to create an `UPDATE` RLS policy. Here's a basic example allowing users to update their own row:

  ```sql
  -- Example UPDATE Policy: Allow users to update their own profile
  -- Replace 'public.profiles' with your actual table name.
  CREATE POLICY "Allow authenticated users update own profile"
  ON public.profiles
  FOR UPDATE
  TO authenticated
  USING (auth.uid() = id) -- Specifies which rows the policy applies to (only their own)
  WITH CHECK (auth.uid() = id); -- Ensures they can't change the 'id' column
  ```

- **Frontend Code:** Adding columns to the database doesn't automatically populate them. You will need to modify your frontend code:
  - To include input fields for this new data (e.g., in the signup form or a separate profile form).
  - To include the new fields in the `insert` or `update` calls made to Supabase (e.g., modifying the `insert` call in `supawp/src/signup.ts` or creating a new update function).
