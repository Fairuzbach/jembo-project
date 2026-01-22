<style>
    .jembo-container {
        background: white;
        border-radius: 12px;
        box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
        overflow: hidden;
        max-width: 600px;
        margin: 0 auto;
    }

    .jembo-header {
        background: linear-gradient(135deg, #1e40af 0%, #3b82f6 100%);
        padding: 24px;
        color: white;
    }

    .jembo-header h2 {
        font-size: 24px;
        font-weight: bold;
        margin: 0 0 8px 0;
    }

    .jembo-header p {
        margin: 0;
        opacity: 0.9;
        font-size: 14px;
    }

    .jembo-form {
        padding: 32px;
    }

    .form-group {
        margin-bottom: 24px;
    }

    .form-label {
        display: block;
        font-weight: bold;
        color: #334155;
        margin-bottom: 8px;
        font-size: 14px;
    }

    .form-input {
        width: 100%;
        padding: 12px 16px;
        border: 2px solid #e2e8f0;
        border-radius: 8px;
        font-size: 15px;
        transition: all 0.3s;
        box-sizing: border-box;
    }

    .form-input:focus {
        outline: none;
        border-color: #fbbf24;
        box-shadow: 0 0 0 3px rgba(251, 191, 36, 0.2);
    }

    .form-input.error {
        border-color: #ef4444;
        background-color: #fef2f2;
    }

    .error-message {
        color: #ef4444;
        font-size: 12px;
        margin-top: 6px;
        display: flex;
        align-items: center;
        gap: 4px;
    }

    .jembo-button {
        background: linear-gradient(135deg, #1e40af 0%, #3b82f6 100%);
        color: white;
        font-weight: bold;
        padding: 14px 32px;
        border: none;
        border-radius: 8px;
        font-size: 15px;
        cursor: pointer;
        transition: all 0.3s;
        box-shadow: 0 4px 12px rgba(30, 64, 175, 0.3);
    }

    .jembo-button:hover {
        background: linear-gradient(135deg, #fbbf24 0%, #f59e0b 100%);
        color: #1e3a8a;
        transform: translateY(-2px);
        box-shadow: 0 6px 16px rgba(251, 191, 36, 0.4);
    }

    .success-alert {
        background: linear-gradient(to right, #ecfdf5, #d1fae5);
        border-left: 4px solid #10b981;
        padding: 16px;
        border-radius: 8px;
        margin-bottom: 24px;
        display: flex;
        align-items: center;
        gap: 12px;
    }

    .success-icon {
        width: 24px;
        height: 24px;
        color: #10b981;
        flex-shrink: 0;
    }

    .success-text {
        color: #065f46;
    }

    .button-container {
        display: flex;
        justify-content: flex-end;
        margin-top: 32px;
    }
</style>

@if (session('status') === 'password-updated')
    <div class="success-alert">
        <svg class="success-icon" fill="currentColor" viewBox="0 0 20 20">
            <path fill-rule="evenodd"
                d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z"
                clip-rule="evenodd" />
        </svg>
        <div class="success-text">
            <strong style="font-weight: bold;">Berhasil!</strong>
            <span style="margin-left: 8px;">Password Anda telah diperbarui.</span>
        </div>
    </div>
@endif

<div class="jembo-container">
    <div class="jembo-header">
        <h2>Ubah Password</h2>
        <p>Perbarui keamanan akun Anda</p>
    </div>

    <form action="{{ route('password.update') }}" method="POST" class="jembo-form">
        @csrf
        @method('PUT')

        <div class="form-group">
            <label for="current_password" class="form-label">Password Lama</label>
            <input type="password" id="current_password" name="current_password"
                class="form-input @error('current_password', 'updatePassword') error @enderror"
                placeholder="Masukkan password lama">
            @error('current_password')
                <div class="error-message">
                    <svg style="width: 16px; height: 16px;" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd"
                            d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z"
                            clip-rule="evenodd" />
                    </svg>
                    {{ $message }}
                </div>
            @enderror
        </div>

        <div class="form-group">
            <label for="password" class="form-label">Password Baru</label>
            <input type="password" id="password" name="password"
                class="form-input @error('password', 'updatePassword') error @enderror"
                placeholder="Masukkan password baru">
            @error('password')
                <div class="error-message">
                    <svg style="width: 16px; height: 16px;" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd"
                            d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z"
                            clip-rule="evenodd" />
                    </svg>
                    {{ $message }}
                </div>
            @enderror
        </div>

        <div class="form-group">
            <label for="password_confirmation" class="form-label">Ulangi Password Baru</label>
            <input type="password" id="password_confirmation" name="password_confirmation" class="form-input"
                placeholder="Konfirmasi password baru">
        </div>

        <div class="button-container">
            <button type="submit" class="jembo-button">
                Simpan Password Baru
            </button>
        </div>
    </form>
</div>
