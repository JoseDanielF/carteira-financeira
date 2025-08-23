const API_URL = 'http://127.0.0.1:8000/api';

let authToken = null;
let userData = null;

const loginPage = document.getElementById('login-page');
const registerPage = document.getElementById('register-page');
const homePage = document.getElementById('home-page');
const notification = document.getElementById('notification');

function showPage(page) {
    loginPage.classList.add('hidden');
    registerPage.classList.add('hidden');
    homePage.classList.add('hidden');
    page.classList.remove('hidden');
}

function showNotification(message, isError = true) {
    notification.textContent = message;
    notification.classList.remove(isError ? 'bg-green-500' : 'bg-red-500');
    notification.classList.add(isError ? 'bg-red-500' : 'bg-green-500');
    notification.classList.remove('opacity-0');
    setTimeout(() => {
        notification.classList.add('opacity-0');
    }, 3000);
}

function updateHomePage() {
    if (!userData || !userData.wallet) return;
    document.getElementById('balance').textContent = `R$ ${parseFloat(userData.wallet.balance).toFixed(2).replace('.', ',')}`;
    document.getElementById('user-info').textContent = `Bem-vindo, ${userData.name}! (ID da Carteira: ${userData.wallet.id})`;
}

async function apiRequest(endpoint, method = 'POST', body = null, requiresAuth = false) {
    const headers = {
        'Content-Type': 'application/json',
        'Accept': 'application/json',
    };
    if (requiresAuth && authToken) {
        headers['Authorization'] = `Bearer ${authToken}`;
    }

    try {
        const response = await fetch(`${API_URL}${endpoint}`, {
            method,
            headers,
            body: body ? JSON.stringify(body) : null,
        });

        if (response.status === 204) {
            return null;
        }

        const data = await response.json();

        if (!response.ok) {
            let errorMessage = 'Ocorreu um erro desconhecido.';
            if (data.errors) {
                errorMessage = Object.values(data.errors).flat()[0];
            } else if (data.message) {
                errorMessage = data.message;
            }
            throw new Error(errorMessage);
        }
        return data;
    } catch (error) {
        showNotification(error.message);
        throw error;
    }
}

function saveSession(token, user) {
    authToken = token;
    userData = user;
    localStorage.setItem('authToken', token);
    localStorage.setItem('userData', JSON.stringify(user));
}

function clearSession() {
    authToken = null;
    userData = null;
    localStorage.removeItem('authToken');
    localStorage.removeItem('userData');
}

async function checkSession() {
    const savedToken = localStorage.getItem('authToken');
    if (savedToken) {
        authToken = savedToken;
        try {
            const userDetails = await apiRequest('/user', 'GET', null, true);
            userData = userDetails;
            updateHomePage();
            showPage(homePage);
        } catch (error) {
            clearSession();
            showPage(loginPage);
        }
    } else {
        showPage(loginPage);
    }
}

document.getElementById('show-register').addEventListener('click', (e) => {
    e.preventDefault();
    showPage(registerPage);
});

document.getElementById('show-login').addEventListener('click', (e) => {
    e.preventDefault();
    showPage(loginPage);
});

document.getElementById('register-form').addEventListener('submit', async (e) => {
    e.preventDefault();
    const name = document.getElementById('register-name').value;
    const email = document.getElementById('register-email').value;
    const password = document.getElementById('register-password').value;
    const password_confirmation = document.getElementById('register-password-confirmation').value;

    try {
        await apiRequest('/register', 'POST', { name, email, password, password_confirmation });
        showNotification('Cadastro realizado com sucesso! Faça o login.', false);
        e.target.reset();
        showPage(loginPage);
    } catch (error) {
        console.error('Falha no registro:', error);
    }
});

document.getElementById('login-form').addEventListener('submit', async (e) => {
    e.preventDefault();
    const email = document.getElementById('login-email').value;
    const password = document.getElementById('login-password').value;

    try {
        const data = await apiRequest('/login', 'POST', { email, password });
        authToken = data.token; 
        
        const userDetails = await apiRequest('/user', 'GET', null, true);
        
        saveSession(data.token, userDetails);
        
        updateHomePage();
        showPage(homePage);
    } catch (error) {
        authToken = null;
        console.error('Falha no login:', error);
    }
});

document.getElementById('logout-button').addEventListener('click', async () => {
     try {
        await apiRequest('/logout', 'POST', null, true);
        clearSession(); 
        document.getElementById('login-form').reset();
        showPage(loginPage);
    } catch (error) {
        console.error('Falha no logout:', error);
    }
});

document.getElementById('deposit-form').addEventListener('submit', async (e) => {
    e.preventDefault();
    const amount = parseFloat(document.getElementById('deposit-amount').value);
    if(isNaN(amount) || amount <= 0) {
        showNotification('Valor de depósito inválido.');
        return;
    }
    try {
        await apiRequest('/deposit', 'POST', { amount }, true);
        const userDetails = await apiRequest('/user', 'GET', null, true);
        
        saveSession(authToken, userDetails);
        
        updateHomePage();
        showNotification('Depósito realizado com sucesso!', false);
        e.target.reset();
    } catch(error) {
        console.error('Falha no depósito:', error);
    }
});

document.getElementById('transfer-form').addEventListener('submit', async (e) => {
    e.preventDefault();
    const payee_wallet_id = parseInt(document.getElementById('transfer-wallet-id').value);
    const amount = parseFloat(document.getElementById('transfer-amount').value);

    if(isNaN(payee_wallet_id) || isNaN(amount) || amount <= 0) {
        showNotification('Dados da transferência inválidos.');
        return;
    }
    if(payee_wallet_id === userData.wallet.id) {
        showNotification('Você não pode transferir para si mesmo.');
        return;
    }

    try {
        await apiRequest('/transfer', 'POST', { payee_wallet_id, amount }, true);
        const userDetails = await apiRequest('/user', 'GET', null, true);
        
        saveSession(authToken, userDetails);

        updateHomePage();
        showNotification('Transferência realizada com sucesso!', false);
        e.target.reset();
    } catch(error) {
        console.error('Falha na transferência:', error);
    }
});

checkSession();
