const API_URL = 'http://127.0.0.1:8000/api';
let currentPage = 1;
let authToken = null;
let userData = null;

const loginPage = document.getElementById('login-page');
const registerPage = document.getElementById('register-page');
const homePage = document.getElementById('home-page');
const historyPage = document.getElementById('history-page');
const notification = document.getElementById('notification');

function showPage(page) {
    loginPage.classList.add('hidden');
    registerPage.classList.add('hidden');
    homePage.classList.add('hidden');
    historyPage.classList.add('hidden');
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
            loadTransactions();
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

async function reverseTransaction(transactionId) {
    if (!confirm('Você tem certeza que deseja reverter esta transação?')) {
        return;
    }

    try {
        const response = await apiRequest(`/transactions/${transactionId}/reverse`, 'POST', null, true);

        showNotification(response.message, false);

        const userDetails = await apiRequest('/user', 'GET', null, true);
        saveSession(authToken, userDetails);
        updateHomePage();
        showPage(homePage);

    } catch (error) {
        console.error('Falha ao reverter transação:', error);
    }
}

async function loadTransactions(page = 1) {
    currentPage = page;
    const historyContainer = document.getElementById('transaction-history');
    try {
        const response = await apiRequest(`/wallet/transactions?page=${page}`, 'GET', null, true);
        const transactions = response.data;
        const currentPageNum = response.current_page;

        if (!transactions || transactions.length === 0) {
            historyContainer.innerHTML = `<p class="p-4 text-gray-500">Nenhuma transação encontrada.</p>`;
            return;
        }

        const transactionsHtml = transactions.map(tx => {
            let descricao = '';

            if (tx.type === 'reversal') {
                descricao = `Estorno de transação`;
            } else if (tx.type === 'transfer' && tx.payer_wallet_id === userData.wallet.id) {
                descricao = `Transferência enviada para ${tx.payee_wallet?.user?.name || 'ID ' + tx.payee_wallet_id}`;
            } else if (tx.type === 'transfer') {
                descricao = `Transferência recebida de ${tx.payer_wallet?.user?.name || 'ID ' + tx.payer_wallet_id}`;
            } else if (tx.type === 'deposit') {
                descricao = `Depósito realizado`;
            }

            let isIncoming = false;
            if (tx.type === 'deposit' || (tx.type === 'transfer' && tx.payee_wallet_id === userData.wallet.id) || (tx.type === 'reversal' && tx.payee_wallet_id === userData.wallet.id)) {
                isIncoming = true;
            }

            const amountClass = isIncoming ? 'text-green-600' : 'text-red-600';
            const amountSign = isIncoming ? '+' : '-';

            let reversalButton = '';
            const canReverse = tx.status !== 'reversed' &&
                (tx.type === 'deposit' || (tx.type === 'transfer' && tx.payer_wallet_id === userData.wallet.id));

            if (canReverse) {
                reversalButton = `
                    <button onclick="reverseTransaction(${tx.id})" class="text-xs bg-orange-500 text-white px-2 py-1 rounded hover:bg-orange-600 transition-colors">
                        Reverter
                    </button>
                `;
            }

            return `
                <div class="p-3 border-b border-gray-200 text-left">
                    <div class="flex justify-between items-start">
                        <div>
                            <p class="text-sm font-semibold text-gray-800">${descricao}</p>
                            <p class="text-xs text-gray-400">${new Date(tx.created_at).toLocaleString('pt-BR')}</p>
                        </div>
                        <p class="text-sm font-bold ${amountClass} whitespace-nowrap">
                            ${amountSign} R$ ${parseFloat(tx.amount).toFixed(2).replace('.', ',')}
                        </p>
                    </div>
                    <div class="mt-2">
                        ${reversalButton}
                    </div>
                </div>
            `;
        }).join('');

        let paginationHtml = `<div class="flex justify-between items-center p-2 bg-gray-100 rounded-b-lg">`;
        paginationHtml += `<button class="px-3 py-1 bg-gray-300 rounded hover:bg-gray-400 disabled:opacity-50" ${!response.prev_page_url ? 'disabled' : ''} onclick="loadTransactions(${currentPageNum - 1})">Anterior</button>`;
        paginationHtml += `<span class="text-sm">Página ${currentPageNum} de ${response.last_page}</span>`;
        paginationHtml += `<button class="px-3 py-1 bg-gray-300 rounded hover:bg-gray-400 disabled:opacity-50" ${!response.next_page_url ? 'disabled' : ''} onclick="loadTransactions(${currentPageNum + 1})">Próxima</button>`;
        paginationHtml += '</div>';

        historyContainer.innerHTML = transactionsHtml + paginationHtml;

    } catch (error) {
        console.error('Erro ao carregar transações:', error);
        historyContainer.innerHTML = `<p class="p-4 text-red-500">Erro ao carregar histórico.</p>`;
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

document.getElementById('show-history-button').addEventListener('click', () => {
    loadTransactions();
    showPage(historyPage);
});
document.getElementById('back-to-home-button').addEventListener('click', () => {
    showPage(homePage);
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
        const loginData = await apiRequest('/login', 'POST', { email, password });

        authToken = loginData.token;

        const userDetails = await apiRequest('/user', 'GET', null, true);

        saveSession(loginData.token, userDetails);

        await loadTransactions();
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
    } catch (error) {
        console.error('Falha no logout (API):', error);
    } finally {
        clearSession();
        document.getElementById('login-form').reset();
        showPage(loginPage);
    }
});

document.getElementById('deposit-form').addEventListener('submit', async (e) => {
    e.preventDefault();
    const amount = parseFloat(document.getElementById('deposit-amount').value);
    if (isNaN(amount) || amount <= 0) {
        showNotification('Valor de depósito inválido.');
        return;
    }
    try {
        await apiRequest('/deposit', 'POST', { amount }, true);
        const userDetails = await apiRequest('/user', 'GET', null, true);
        saveSession(authToken, userDetails);
        updateHomePage();
        await loadTransactions();
        showNotification('Depósito realizado com sucesso!', false);
        e.target.reset();
    } catch (error) {
        console.error('Falha no depósito:', error);
    }
});

document.getElementById('transfer-form').addEventListener('submit', async (e) => {
    e.preventDefault();
    const payee_wallet_id = parseInt(document.getElementById('transfer-wallet-id').value);
    const amount = parseFloat(document.getElementById('transfer-amount').value);

    if (isNaN(payee_wallet_id) || isNaN(amount) || amount <= 0) {
        showNotification('Dados da transferência inválidos.');
        return;
    }
    if (payee_wallet_id === userData.wallet.id) {
        showNotification('Você não pode transferir para si mesmo.');
        return;
    }

    try {
        await apiRequest('/transfer', 'POST', { payee_wallet_id, amount }, true);
        const userDetails = await apiRequest('/user', 'GET', null, true);
        saveSession(authToken, userDetails);
        updateHomePage();
        await loadTransactions();
        showNotification('Transferência realizada com sucesso!', false);
        e.target.reset();
    } catch (error) {
        console.error('Falha na transferência:', error);
    }
});

checkSession();