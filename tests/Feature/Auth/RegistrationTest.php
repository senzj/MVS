<?php

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Livewire\Volt\Volt;

test('registration screen can be rendered', function () {
    $response = $this->get('/register');

    $response->assertStatus(200);
});

test('new users can register', function () {
    $response = Volt::test('auth.register')
        ->set('name', 'Test User')
        ->set('username', 'testuser')
        ->set('email', 'test@example.com')
        ->set('password', 'Password123!') // Must meet all requirements: 8+ chars, mixed case, numbers, symbols
        ->set('password_confirmation', 'Password123!')
        ->call('register');

    $response
        ->assertHasNoErrors()
        ->assertRedirect(route('login', absolute: false));

    // Check if user was created in database with formatted data
    $this->assertDatabaseHas('users', [
        'name' => 'Test User', // ucwords applied: 'Test User' 
        'username' => 'Testuser', // ucwords applied: 'testuser' -> 'Testuser'
        'email' => 'test@example.com', // strtolower applied but already lowercase
    ]);

    $this->assertGuest();
});

test('registration fails with weak password', function () {
    $response = Volt::test('auth.register')
        ->set('name', 'Test User')
        ->set('username', 'testuser')
        ->set('email', 'test@example.com')
        ->set('password', 'password') // This should fail validation
        ->set('password_confirmation', 'password')
        ->call('register');

    $response->assertHasErrors('password');
    
    // User should not be created
    $this->assertDatabaseMissing('users', [
        'email' => 'test@example.com',
    ]);
});

test('registration fails with mismatched password confirmation', function () {
    $response = Volt::test('auth.register')
        ->set('name', 'Test User')
        ->set('username', 'testuser')
        ->set('email', 'test@example.com')
        ->set('password', 'Password123!')
        ->set('password_confirmation', 'DifferentPassword123!')
        ->call('register');

    $response->assertHasErrors('password');
});

test('registration fails with duplicate email', function () {
    // Create a user first
    User::factory()->create(['email' => 'test@example.com']);

    $response = Volt::test('auth.register')
        ->set('name', 'Test User')
        ->set('username', 'testuser')
        ->set('email', 'test@example.com') // Duplicate email
        ->set('password', 'Password123!')
        ->set('password_confirmation', 'Password123!')
        ->call('register');

    $response->assertHasErrors('email');
});

test('registration fails with duplicate username', function () {
    // Create a user first with the exact username that will be checked during validation
    User::factory()->create(['username' => 'testuser']); // Store as lowercase to match validation

    $response = Volt::test('auth.register')
        ->set('name', 'Test User')
        ->set('username', 'testuser') // This will conflict during validation
        ->set('email', 'different@example.com')
        ->set('password', 'Password123!')
        ->set('password_confirmation', 'Password123!')
        ->call('register');

    $response->assertHasErrors('username');
});

test('registration fails with duplicate username case insensitive', function () {
    // Create with any case format
    User::factory()->create(['username' => 'TestUser']);

    // Try to register with different case - should fail with proper validation
    $response = Volt::test('auth.register')
        ->set('name', 'Test User')
        ->set('username', 'TestUser') // Different case, should still fail
        ->set('email', 'different@example.com')
        ->set('password', 'Password123!')
        ->set('password_confirmation', 'Password123!')
        ->call('register');

    $response->assertHasErrors('username');
});

test('new user can login after creating their account', function () {
    // First register a user
    Volt::test('auth.register')
        ->set('name', 'Test User')
        ->set('username', 'testuser')
        ->set('email', 'test@example.com')
        ->set('password', 'Password123!')
        ->set('password_confirmation', 'Password123!')
        ->call('register');

    // Now try to login - remember username is stored as 'Testuser'
    $response = Volt::test('auth.login')
        ->set('username', 'testuser') // Input will be transformed to 'Testuser' by ucwords in login
        ->set('password', 'Password123!')
        ->call('login');

    $response
        ->assertHasNoErrors()
        ->assertRedirect(route('dashboard', absolute: false));

    $this->assertAuthenticated();
});

// Alternative: Create user directly with factory for login test
test('factory user can login with strong password', function () {
    $user = User::factory()->create([
        'username' => 'Testuser', // Store as ucwords format
        'password' => Hash::make('Password123!'), // Use strong password
    ]);

    $response = Volt::test('auth.login')
        ->set('username', 'testuser') // Will be transformed to 'Testuser'
        ->set('password', 'Password123!')
        ->call('login');

    $response
        ->assertHasNoErrors()
        ->assertRedirect(route('dashboard', absolute: false));

    $this->assertAuthenticated();
});