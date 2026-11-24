/**
 * Local Demo Store & Persistence for GitHub Pages
 */
const DEFAULT_TRANSACTIONS = [
    { id: 1, date: 'Nov 16, 2026', rawDate: '2026-11-16', type: 'expense', category: 'Food & Dining', description: 'Casual Dinner with University Friends', amount: 42.50 },
    { id: 2, date: 'Nov 15, 2026', rawDate: '2026-11-15', type: 'expense', category: 'Entertainment', description: 'Cinema Tickets & Popcorn Combo', amount: 35.00 },
    { id: 3, date: 'Nov 14, 2026', rawDate: '2026-11-14', type: 'expense', category: 'Shopping', description: 'Ergonomic Desk Chair Cushions', amount: 129.99 },
    { id: 4, date: 'Nov 13, 2026', rawDate: '2026-11-13', type: 'income', category: 'Freelance', description: 'Web Design Freelance Project', amount: 850.00 },
    { id: 5, date: 'Nov 12, 2026', rawDate: '2026-11-12', type: 'expense', category: 'Travel & Transport', description: 'Metro Transit Card Recharge', amount: 45.00 },
    { id: 6, date: 'Nov 10, 2026', rawDate: '2026-11-10', type: 'expense', category: 'Food & Dining', description: 'Weekly Grocery Supermarket Trip', amount: 88.20 },
    { id: 7, date: 'Nov 08, 2026', rawDate: '2026-11-08', type: 'expense', category: 'Bills & Utilities', description: 'High-Speed Fiber Internet & Electricity', amount: 145.50 },
    { id: 8, date: 'Nov 05, 2026', rawDate: '2026-11-05', type: 'expense', category: 'Housing & Rent', description: 'Apartment Monthly Rent', amount: 1100.00 },
    { id: 9, date: 'Nov 02, 2026', rawDate: '2026-11-02', type: 'income', category: 'Salary', description: 'Monthly Corporate Salary', amount: 4500.00 }
];

function getDemoTransactions() {
    const stored = localStorage.getItem('demo_transactions');
    if (!stored) {
        localStorage.setItem('demo_transactions', JSON.stringify(DEFAULT_TRANSACTIONS));
        return DEFAULT_TRANSACTIONS;
    }
    try {
        return JSON.parse(stored);
    } catch(e) {
        return DEFAULT_TRANSACTIONS;
    }
}

function saveDemoTransactions(txs) {
    localStorage.setItem('demo_transactions', JSON.stringify(txs));
}

function addDemoTransaction(tx) {
    const list = getDemoTransactions();
    tx.id = Date.now();
    list.unshift(tx);
    saveDemoTransactions(list);
}

function deleteDemoTransaction(id) {
    let list = getDemoTransactions();
    list = list.filter(item => item.id !== id);
    saveDemoTransactions(list);
}
