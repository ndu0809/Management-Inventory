<script setup lang="ts">
import { ref } from 'vue'
import { useRouter } from 'vue-router'
import api from '../services/api'

const router = useRouter()

const email = ref('')
const password = ref('')
const loading = ref(false)
const errorMessage = ref('')
const showPassword = ref(false)

async function handleLogin() {
    errorMessage.value = ''
    loading.value = true

    try {
        const response = await api.post('/login', {
            email: email.value,
            password: password.value,
        })

        localStorage.setItem('auth_token', response.data.token)
        localStorage.setItem('auth_user', JSON.stringify(response.data.user))

        await router.push('/')
    } catch (error: any) {
        errorMessage.value =
            error.response?.data?.message ??
            'Tidak dapat terhubung ke server. Pastikan backend Laravel berjalan.'
    } finally {
        loading.value = false
    }
}
</script>

<template>
    <main class="login-page">
        <form class="login-card" @submit.prevent="handleLogin">
            <div class="brand-icon">m</div>

            <h1>Selamat Datang</h1>
            <p class="subtitle">Masuk ke Management Inventaris</p>

            <div class="form-field">
                <label for="email">Email</label>
                <input
                    id="email"
                    v-model.trim="email"
                    type="email"
                    placeholder="admin@gmail.com"
                    autocomplete="username"
                    required
                />
            </div>

            <div class="form-field">
                <label for="password">Password</label>

                <div class="password-field">
                    <input
                        id="password"
                        v-model="password"
                        :type="showPassword ? 'text' : 'password'"
                        placeholder="Masukkan password"
                        autocomplete="current-password"
                        required
                    />

                    <button
                        type="button"
                        class="show-password"
                        @click="showPassword = !showPassword"
                    >
                        {{ showPassword ? 'Sembunyikan' : 'Lihat' }}
                    </button>
                </div>
            </div>

            <p v-if="errorMessage" class="error-message" role="alert">
                {{ errorMessage }}
            </p>

            <button class="login-button" type="submit" :disabled="loading">
                {{ loading ? 'Memproses...' : 'Masuk' }}
            </button>

            <p class="footer-text">Management Inventaris</p>
        </form>
    </main>
</template>

<style scoped>
.login-page {
    position: fixed;
    inset: 0;
    z-index: 100;

    display: grid;
    place-items: center;

    width: 100%;
    height: 100vh;
    height: 100dvh;
    margin: 0;
    padding: 24px;

    overflow-y: auto;
    background: #f7f8fa;
    font-family: Inter, Arial, sans-serif;
}

.login-card {
    width: 100%;
    max-width: 400px;
    margin: auto;
    padding: 36px;
    border: 1px solid #ededed;
    border-radius: 18px;
    background: white;
    box-shadow: 0 12px 40px #00000008;
}

@media (max-width: 480px) {
    .login-page {
        padding: 16px;
    }

    .login-card {
        padding: 26px 22px;
    }
}

.brand-icon {
    display: grid;
    place-items: center;
    width: 42px;
    height: 42px;
    border-radius: 12px;
    background: #050505;
    color: #d7f58b;
    font-size: 25px;
    font-weight: 700;
}

h1 {
    margin: 24px 0 8px;
    color: #202020;
    font-size: 25px;
}

.subtitle {
    margin: 0 0 28px;
    color: #777;
    font-size: 13px;
}

.form-field {
    display: grid;
    gap: 8px;
    margin-bottom: 18px;
}

.form-field label {
    color: #333;
    font-size: 13px;
    font-weight: 600;
}

.form-field input {
    box-sizing: border-box;
    width: 100%;
    min-width: 0;
    height: 44px;
    padding: 0 12px;
    border: 1px solid #ddd;
    border-radius: 9px;
    outline: none;
    background: white;
    color: #222;
    font-size: 13px;
}

.form-field input:focus {
    border-color: #555;
}

.password-field {
    display: flex;
    align-items: center;
    gap: 8px;
    padding-right: 10px;
    border: 1px solid #ddd;
    border-radius: 9px;
}

.password-field input {
    border: 0;
}

.password-field:focus-within {
    border-color: #555;
}

.show-password {
    flex-shrink: 0;
    padding: 5px 0;
    border: 0;
    background: transparent;
    color: #555;
    font-size: 11px;
}

.error-message {
    color: #b42318;
    font-size: 12px;
    line-height: 1.5;
}

.login-button {
    width: 100%;
    height: 44px;
    margin-top: 8px;
    border: 0;
    border-radius: 9px;
    background: #080808;
    color: #d7f58b;
    font-weight: 600;
    cursor: pointer;
}

.login-button:disabled {
    cursor: wait;
    opacity: 0.6;
}

.footer-text {
    margin: 24px 0 0;
    color: #999;
    font-size: 11px;
    text-align: center;
}
</style>
