<script setup lang="ts">
import axios from 'axios';
import { computed, onMounted, reactive, ref } from 'vue';
import type { DashboardMetrics, Ticket, TicketPriority, TicketStatus } from './types';

const tickets = ref<Ticket[]>([]);
const metrics = ref<DashboardMetrics>({ total: 0, open: 0, in_progress: 0, resolved: 0, critical: 0, sla_breached: 0 });
const loading = ref(true);
const submitting = ref(false);
const error = ref('');
const filter = ref<'all' | TicketStatus>('all');

const form = reactive({
    requester_name: '',
    requester_email: '',
    subject: '',
    description: '',
    priority: 'medium' as TicketPriority,
});

const visibleTickets = computed(() =>
    filter.value === 'all' ? tickets.value : tickets.value.filter(ticket => ticket.status === filter.value),
);

const priorityLabel = (priority: TicketPriority) => priority.charAt(0).toUpperCase() + priority.slice(1);
const statusLabel = (status: TicketStatus) => status.replace('_', ' ').replace(/\b\w/g, letter => letter.toUpperCase());

async function loadData() {
    loading.value = true;
    error.value = '';

    try {
        const [ticketResponse, dashboardResponse] = await Promise.all([
            axios.get('/api/tickets'),
            axios.get('/api/dashboard'),
        ]);

        tickets.value = ticketResponse.data.data;
        metrics.value = dashboardResponse.data;
    } catch {
        error.value = 'Could not load PulseDesk data. Make sure the Laravel API and database are running.';
    } finally {
        loading.value = false;
    }
}

async function createTicket() {
    submitting.value = true;
    error.value = '';

    try {
        await axios.post('/api/tickets', form);
        Object.assign(form, {
            requester_name: '',
            requester_email: '',
            subject: '',
            description: '',
            priority: 'medium',
        });
        await loadData();
    } catch (exception: any) {
        error.value = exception?.response?.data?.message ?? 'Unable to create the ticket.';
    } finally {
        submitting.value = false;
    }
}

async function advanceStatus(ticket: Ticket) {
    const transitions: Record<TicketStatus, TicketStatus> = {
        open: 'in_progress',
        in_progress: 'resolved',
        resolved: 'closed',
        closed: 'open',
    };

    try {
        await axios.patch(`/api/tickets/${ticket.id}`, { status: transitions[ticket.status] });
        await loadData();
    } catch {
        error.value = 'Unable to update ticket status.';
    }
}

onMounted(loadData);
</script>

<template>
    <div class="shell">
        <aside class="sidebar">
            <div class="brand">
                <div class="brand-mark">PD</div>
                <div>
                    <strong>PulseDesk</strong>
                    <span>Support operations</span>
                </div>
            </div>

            <nav>
                <a class="active" href="#dashboard">Overview</a>
                <a href="#tickets">Tickets</a>
                <a href="#new-ticket">New ticket</a>
                <a href="https://github.com/Arondith/Laravel" target="_blank" rel="noreferrer">GitHub ↗</a>
            </nav>

            <div class="sidebar-note">
                <span class="eyebrow">Stack</span>
                <p>Laravel 13 · Vue 3 · TypeScript · REST · MySQL/SQLite · Docker</p>
            </div>
        </aside>

        <main>
            <header class="topbar" id="dashboard">
                <div>
                    <p class="eyebrow">Helpdesk analytics</p>
                    <h1>Support operations at a glance.</h1>
                    <p class="lede">A full-stack portfolio project for tracking requests, priorities, SLA deadlines, and resolution progress.</p>
                </div>
                <button class="secondary" @click="loadData">Refresh data</button>
            </header>

            <div v-if="error" class="alert">{{ error }}</div>

            <section class="metric-grid">
                <article class="metric">
                    <span>Total tickets</span>
                    <strong>{{ metrics.total }}</strong>
                    <small>All recorded requests</small>
                </article>
                <article class="metric">
                    <span>Open</span>
                    <strong>{{ metrics.open }}</strong>
                    <small>Waiting for action</small>
                </article>
                <article class="metric">
                    <span>In progress</span>
                    <strong>{{ metrics.in_progress }}</strong>
                    <small>Currently being handled</small>
                </article>
                <article class="metric danger">
                    <span>SLA breached</span>
                    <strong>{{ metrics.sla_breached }}</strong>
                    <small>{{ metrics.critical }} active critical</small>
                </article>
            </section>

            <section class="content-grid">
                <div class="panel" id="tickets">
                    <div class="panel-heading">
                        <div>
                            <p class="eyebrow">Queue</p>
                            <h2>Recent tickets</h2>
                        </div>
                        <select v-model="filter" aria-label="Filter tickets by status">
                            <option value="all">All statuses</option>
                            <option value="open">Open</option>
                            <option value="in_progress">In progress</option>
                            <option value="resolved">Resolved</option>
                            <option value="closed">Closed</option>
                        </select>
                    </div>

                    <div v-if="loading" class="empty">Loading tickets…</div>
                    <div v-else-if="visibleTickets.length === 0" class="empty">No tickets match this filter.</div>
                    <div v-else class="ticket-list">
                        <article v-for="ticket in visibleTickets" :key="ticket.id" class="ticket">
                            <div class="ticket-main">
                                <div class="ticket-meta">
                                    <span>{{ ticket.reference }}</span>
                                    <span :class="['badge', `priority-${ticket.priority}`]">{{ priorityLabel(ticket.priority) }}</span>
                                    <span :class="['badge', 'status']">{{ statusLabel(ticket.status) }}</span>
                                    <span v-if="ticket.sla_breached" class="badge breach">SLA breached</span>
                                </div>
                                <h3>{{ ticket.subject }}</h3>
                                <p>{{ ticket.requester_name }} · {{ ticket.requester_email }}</p>
                            </div>
                            <button class="link-button" @click="advanceStatus(ticket)">
                                Advance status →
                            </button>
                        </article>
                    </div>
                </div>

                <div class="panel composer" id="new-ticket">
                    <div class="panel-heading">
                        <div>
                            <p class="eyebrow">Create request</p>
                            <h2>New support ticket</h2>
                        </div>
                    </div>

                    <form @submit.prevent="createTicket">
                        <label>
                            Requester name
                            <input v-model="form.requester_name" required placeholder="Jordan Reyes">
                        </label>
                        <label>
                            Email
                            <input v-model="form.requester_email" type="email" required placeholder="jordan@example.com">
                        </label>
                        <label>
                            Subject
                            <input v-model="form.subject" required placeholder="Describe the issue briefly">
                        </label>
                        <label>
                            Priority
                            <select v-model="form.priority">
                                <option value="low">Low · 72h SLA</option>
                                <option value="medium">Medium · 24h SLA</option>
                                <option value="high">High · 8h SLA</option>
                                <option value="critical">Critical · 2h SLA</option>
                            </select>
                        </label>
                        <label>
                            Description
                            <textarea v-model="form.description" required rows="5" placeholder="What happened, what was expected, and any troubleshooting already attempted?"></textarea>
                        </label>
                        <button class="primary" type="submit" :disabled="submitting">
                            {{ submitting ? 'Creating…' : 'Create ticket' }}
                        </button>
                    </form>
                </div>
            </section>
        </main>
    </div>
</template>
