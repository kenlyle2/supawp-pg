# Testing Guide for SupaWP

This guide explains how to write and run tests for the SupaWP TypeScript code in the Nx monorepo.

## Table of Contents

- [Setup](#setup)
- [Running Tests](#running-tests)
- [Writing Tests](#writing-tests)
- [Test Structure](#test-structure)
- [Mocking](#mocking)
- [Coverage](#coverage)
- [Best Practices](#best-practices)
- [Examples](#examples)

---

## Setup

### Prerequisites

The testing infrastructure is already configured with:
- **Jest** - Test runner
- **ts-jest** - TypeScript support for Jest
- **jest-environment-jsdom** - DOM testing environment
- **@types/jest** - TypeScript types

### Configuration Files

1. **`jest.config.js`** - Main Jest configuration
2. **`jest.setup.js`** - Global test setup (runs before all tests)
3. **`tsconfig.json`** - TypeScript configuration (already supports tests)

---

## Running Tests

### Run All Tests

```bash
npm run test
```

### Run Tests in Watch Mode

```bash
npm run test -- --watch
```

### Run Specific Test File

```bash
npm run test -- signup.test.ts
```

### Run Tests with Coverage

```bash
npm run test -- --coverage
```

### Run Tests for Specific Plugin

```bash
npm run test -- supawp/src
```

---

## Writing Tests

### File Structure

Place test files in a `__tests__` directory next to the source files:

```
supawp/
├── src/
│   ├── __tests__/
│   │   ├── signup.test.ts
│   │   ├── login.test.ts
│   │   └── otp-login.test.ts
│   ├── signup.ts
│   ├── login.ts
│   └── otp-login.ts
```

Alternatively, use `.spec.ts` suffix:

```
supawp/
├── src/
│   ├── signup.ts
│   ├── signup.spec.ts
│   ├── login.ts
│   └── login.spec.ts
```

### Basic Test Template

```typescript
import { describe, it, expect, beforeEach, jest } from '@jest/globals';

// Mock dependencies
jest.mock('../supabase-core', () => ({
  initSupabase: () => ({
    auth: {
      signUp: jest.fn(),
    }
  })
}));

jest.mock('../translations', () => ({
  auth: {
    get: (key: string, fallback?: string) => fallback || key,
  },
}));

jest.mock('@techcater-core/libs/js', () => ({
  debugLog: jest.fn(),
  errorLog: jest.fn(),
}));

describe('Feature Name', () => {
  beforeEach(() => {
    // Reset mocks and setup DOM
    jest.clearAllMocks();

    document.body.innerHTML = `
      <div id="test-container"></div>
    `;

    // Setup global config
    (window as any).SupaWPConfig = {
      supabaseUrl: 'https://test.supabase.co',
      supabaseAnonKey: 'test-key',
      emailVerificationMethod: 'otp_token',
    };
  });

  it('should do something', () => {
    // Arrange
    const expected = 'value';

    // Act
    const result = someFunction();

    // Assert
    expect(result).toBe(expected);
  });
});
```

---

## Test Structure

### Organize with describe blocks

```typescript
describe('Signup Flow', () => {
  describe('Email Verification', () => {
    describe('OTP Token Method', () => {
      it('should show verification form after signup', () => {
        // Test implementation
      });

      it('should validate 6-digit code', () => {
        // Test implementation
      });
    });

    describe('Magic Link Method', () => {
      it('should show confirmation message', () => {
        // Test implementation
      });
    });
  });
});
```

### Use beforeEach for setup

```typescript
describe('Login Form', () => {
  let form: HTMLFormElement;
  let mockSignIn: jest.Mock;

  beforeEach(() => {
    // Setup runs before each test
    mockSignIn = jest.fn();

    document.body.innerHTML = `
      <form id="login-form">
        <input id="email" />
        <input id="password" />
      </form>
    `;

    form = document.getElementById('login-form') as HTMLFormElement;
  });

  it('should submit form', () => {
    // Test uses setup from beforeEach
    expect(form).toBeTruthy();
  });
});
```

---

## Mocking

### Mock Supabase Client

```typescript
const mockSignUp = jest.fn();
const mockSignIn = jest.fn();
const mockVerifyOtp = jest.fn();

jest.mock('../supabase-core', () => ({
  initSupabase: () => ({
    auth: {
      signUp: mockSignUp,
      signInWithPassword: mockSignIn,
      verifyOtp: mockVerifyOtp,
      resend: jest.fn(),
    }
  })
}));

// In test
mockSignUp.mockResolvedValue({
  data: { user: { email: 'test@example.com' } },
  error: null
});
```

### Mock Translations

```typescript
jest.mock('../translations', () => ({
  auth: {
    get: (key: string, fallback?: string) => fallback || key,
  },
  mapSupabaseError: (error: any) => error.message || 'Error'
}));
```

### Mock WordPress Auto-Login

```typescript
jest.mock('../auth', () => ({
  handleWordPressAutoLogin: jest.fn(),
}));
```

### Mock Debug Utilities

```typescript
jest.mock('@techcater-core/libs/js', () => ({
  debugLog: jest.fn(),
  errorLog: jest.fn(),
}));
```

### Mock Window Config

```typescript
beforeEach(() => {
  (window as any).SupaWPConfig = {
    supabaseUrl: 'https://test.supabase.co',
    supabaseAnonKey: 'test-key',
    emailVerificationMethod: 'otp_token',
    wpAutoLoginEnabled: 'off',
    authMethods: ['email', 'email_otp_token'],
    translations: {},
  };
});
```

---

## Coverage

### Generate Coverage Report

```bash
npm run test -- --coverage
```

### Coverage Report Location

Coverage reports are saved to:
```
coverage/
├── lcov-report/
│   └── index.html  (Open in browser)
├── coverage-summary.json
└── lcov.info
```

### View Coverage in Browser

```bash
open coverage/lcov-report/index.html
```

### Coverage Thresholds

Add to `jest.config.js`:

```javascript
module.exports = {
  // ... other config
  coverageThresholds: {
    global: {
      branches: 80,
      functions: 80,
      lines: 80,
      statements: 80,
    },
  },
};
```

---

## Best Practices

### 1. Follow AAA Pattern

```typescript
it('should verify email with valid code', () => {
  // Arrange - Setup test data
  const email = 'test@example.com';
  const code = '123456';
  mockVerifyOtp.mockResolvedValue({ data: { user: {} }, error: null });

  // Act - Execute the code
  verifyEmail(email, code);

  // Assert - Check results
  expect(mockVerifyOtp).toHaveBeenCalledWith({
    email,
    token: code,
    type: 'email'
  });
});
```

### 2. Test One Thing Per Test

```typescript
// Good ✅
it('should show error for invalid email', () => {
  // Test only email validation
});

it('should show error for invalid password', () => {
  // Test only password validation
});

// Bad ❌
it('should validate form', () => {
  // Tests email, password, and submission - too much!
});
```

### 3. Use Descriptive Test Names

```typescript
// Good ✅
it('should show verification form when user signs up with unconfirmed email', () => {});

// Bad ❌
it('should work', () => {});
it('test1', () => {});
```

### 4. Clean Up After Tests

```typescript
afterEach(() => {
  // Clean up DOM
  document.body.innerHTML = '';

  // Clear all mocks
  jest.clearAllMocks();

  // Reset modules
  jest.resetModules();
});
```

### 5. Test Edge Cases

```typescript
describe('Verification Code Validation', () => {
  it('should accept valid 6-digit code', () => {
    expect(validateCode('123456')).toBe(true);
  });

  it('should reject code with less than 6 digits', () => {
    expect(validateCode('12345')).toBe(false);
  });

  it('should reject code with more than 6 digits', () => {
    expect(validateCode('1234567')).toBe(false);
  });

  it('should reject code with letters', () => {
    expect(validateCode('12345a')).toBe(false);
  });

  it('should reject empty code', () => {
    expect(validateCode('')).toBe(false);
  });

  it('should reject null/undefined', () => {
    expect(validateCode(null as any)).toBe(false);
    expect(validateCode(undefined as any)).toBe(false);
  });
});
```

### 6. Test Async Code Properly

```typescript
// Good ✅
it('should handle async signup', async () => {
  mockSignUp.mockResolvedValue({ data: {}, error: null });

  await signupUser('test@example.com', 'password');

  expect(mockSignUp).toHaveBeenCalled();
});

// Bad ❌
it('should handle async signup', () => {
  // Missing await - test finishes before async code completes
  signupUser('test@example.com', 'password');
  expect(mockSignUp).toHaveBeenCalled();
});
```

### 7. Use Waitfor for DOM Updates

```typescript
import { waitFor } from '@testing-library/dom';

it('should show verification form after signup', async () => {
  signupUser();

  await waitFor(() => {
    const form = document.getElementById('verification-form');
    expect(form).toBeTruthy();
  });
});
```

---

## Examples

### Example 1: Testing Form Submission

```typescript
it('should submit signup form with valid data', async () => {
  // Arrange
  mockSignUp.mockResolvedValue({
    data: { user: { email: 'test@example.com' } },
    error: null
  });

  const { setupSignupForm } = await import('../signup');
  setupSignupForm();

  const emailInput = document.getElementById('supawp-signup-email') as HTMLInputElement;
  const passwordInput = document.getElementById('supawp-signup-password') as HTMLInputElement;

  emailInput.value = 'test@example.com';
  passwordInput.value = 'password123';

  // Act
  const form = document.getElementById('supawp-signup-form') as HTMLFormElement;
  const submitEvent = new Event('submit', { bubbles: true, cancelable: true });
  form.dispatchEvent(submitEvent);

  // Wait for async
  await new Promise(resolve => setTimeout(resolve, 100));

  // Assert
  expect(mockSignUp).toHaveBeenCalledWith(
    expect.objectContaining({
      email: 'test@example.com',
      password: 'password123'
    })
  );
});
```

### Example 2: Testing Error Handling

```typescript
it('should display error message when signup fails', async () => {
  // Arrange
  mockSignUp.mockResolvedValue({
    data: null,
    error: { message: 'Email already exists' }
  });

  const { setupSignupForm } = await import('../signup');
  setupSignupForm();

  // Act
  const form = document.getElementById('supawp-signup-form') as HTMLFormElement;
  form.dispatchEvent(new Event('submit'));

  await new Promise(resolve => setTimeout(resolve, 100));

  // Assert
  const message = document.getElementById('supawp-signup-message');
  expect(message?.classList.contains('error')).toBe(true);
  expect(message?.textContent).toContain('Email already exists');
});
```

### Example 3: Testing User Interaction

```typescript
it('should toggle between password and OTP login', () => {
  // Arrange
  const { setupLoginForm } = await import('../login');
  setupLoginForm();

  const passwordForm = document.getElementById('password-login');
  const otpForm = document.getElementById('otp-login');
  const toggleButton = document.getElementById('toggle-otp');

  // Act
  toggleButton?.dispatchEvent(new Event('click'));

  // Assert
  expect(passwordForm?.style.display).toBe('none');
  expect(otpForm?.style.display).toBe('block');
});
```

### Example 4: Testing Configuration

```typescript
describe('Verification Method Configuration', () => {
  it('should use OTP token when configured', () => {
    (window as any).SupaWPConfig.emailVerificationMethod = 'otp_token';

    const method = getVerificationMethod();

    expect(method).toBe('otp_token');
  });

  it('should default to magic link when not configured', () => {
    delete (window as any).SupaWPConfig.emailVerificationMethod;

    const method = getVerificationMethod();

    expect(method).toBe('magic_link');
  });
});
```

---

## Common Issues

### Issue 1: Module Not Found

**Error:**
```
Cannot find module '../signup'
```

**Solution:**
```typescript
// Use dynamic import
const { setupSignupForm } = await import('../signup');

// Or mock the module first
jest.mock('../signup', () => ({
  setupSignupForm: jest.fn(),
}));
```

### Issue 2: Async Tests Timing Out

**Error:**
```
Timeout - Async callback was not invoked within the 5000 ms timeout
```

**Solution:**
```typescript
// Increase timeout
it('should complete async operation', async () => {
  // test code
}, 10000); // 10 seconds

// Or use done callback
it('should complete', (done) => {
  asyncOperation().then(() => {
    expect(something).toBe(true);
    done();
  });
});
```

### Issue 3: DOM Not Updating

**Error:**
```
Expected element to be visible but it wasn't
```

**Solution:**
```typescript
// Wait for DOM updates
await new Promise(resolve => setTimeout(resolve, 0));

// Or use waitFor
import { waitFor } from '@testing-library/dom';

await waitFor(() => {
  expect(element).toBeVisible();
});
```

---

## Continuous Integration

### GitHub Actions Example

```yaml
name: Tests

on: [push, pull_request]

jobs:
  test:
    runs-on: ubuntu-latest

    steps:
      - uses: actions/checkout@v2

      - name: Setup Node.js
        uses: actions/setup-node@v2
        with:
          node-version: '20'

      - name: Install dependencies
        run: npm install

      - name: Run tests
        run: npm test

      - name: Upload coverage
        uses: codecov/codecov-action@v2
        with:
          files: ./coverage/lcov.info
```

---

## Resources

- [Jest Documentation](https://jestjs.io/)
- [TypeScript Jest](https://kulshekhar.github.io/ts-jest/)
- [Testing Library](https://testing-library.com/)
- [Jest DOM Matchers](https://github.com/testing-library/jest-dom)

---

## Next Steps

1. Write tests for all new features
2. Aim for >80% code coverage
3. Run tests before committing
4. Add tests to CI/CD pipeline
5. Document complex test scenarios

Happy Testing! 🧪
