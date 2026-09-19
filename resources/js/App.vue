<script setup lang="ts">
import axios from 'axios';
import { computed, onMounted, reactive, ref } from 'vue';
import type { DashboardMetrics, Ticket, TicketPriority, TicketStatus } from './types';

const tickets = ref<Ticket[]>([]);
const metrics = ref<DashboardMetrics>({
    total: 0,
    open: 0,
    in_progress: 0,
    resolved: 0,
    critical: 0,
    unassigned: 0,
    sla_breached: 0,
    average_resolution_minutes: null,
});
const loading = ref(true);
const submitting = ref(false);
const noteSubmitting = ref(false);
const error = ref('');
const filter = ref<'all' | TicketStatus>('all');
const search = ref('');
const selectedTicket = ref<Ticket | null>(null);

const form = reactive({
    requester_name: '',
    requester_email: '',
    subject: '',
    description: '',
    priority: 'medium' as TicketPriority,
});

const noteForm = reactive({
    author_name: 'Support Agent',
    body: '',
});

const visibleTickets = computed(() =>
    filter.value === 'all' ? tickets.value : tickets.value.filter(ticket => ticket.status === filter.value),
);

const averageResolutionLabel = computed(() => {
    const minutes = metrics.value.average_resolution_minutes;

    if (minutes === null) return 'No resolved data';
    if (minutes < 60) return `${minutes} min average`;

    return `${(minutes / 60).toFixed(1)}h average`;
});

const priorityLabel = (priority: TicketPriority) => priority.charAt(0).toUpperCase() + priority.slice(1);
const statusLabel = (status: TicketStatus) => status.replace('_', ' ').replace(/\b\w/g, letter => letter.toUpperCase());
const formatDate = (date: string) => new Intl.DateTimeFormat(undefined, {
    dateStyle: 'medium',
    timeStyle: 'short',
}).format(new Date(date));

async function loadData() {
    loading.value = true;
    error.value = '';

    try {
        const [ticketResponse, dashboardResponse] = await Promise.all([
            axios.get('/api/tickets', { params: search.value ? { search: search.value } : {} }),
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

        if (selectedTicket.value?.id === ticket.id) {
            await openTicket(ticket.id);
        }
    } catch {
        error.value = 'Unable to update ticket status.';
    }
}

async function openTicket(id: number) {
    error.value = '';

    try {
        const response = await axios.get(`/api/tickets/${id}`);
        selectedTicket.value = response.data.data;
    } catch {
        error.value = 'Unable to load ticket details.';
    }
}

async function addNote() {
    if (!selectedTicket.value || !noteForm.body.trim()) return;

    noteSubmitting.value = true;
    error.value = '';

    try {
        await axios.post(`/api/tickets/${selectedTicket.value.id}/notes`, noteForm);
        noteForm.body = '';
        await openTicket(selectedTicket.value.id);
        await loadData();
    } catch (exception: any) {
        error.value = exception?.response?.data?.message ?? 'Unable to add the internal note.';
    } finally {
        noteSubmitting.value = false;
    }
}

function clearSearch() {
    search.value = '';
    loadData();
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
                    <p class="lede">A full-stack portfolio project for tracking requests, SLA deadlines, searchable ticket history, and internal support notes.</p>
                </div>
                <button class="secondary" @click="loadData">Refresh data</button>
            </header>

            <div v-if="error" class="alert">{{ error }}</div>

            <section class="metric-grid">
                <article class="metric">
                    <span>Total tickets</span>
                    <strong>{{ metrics.total }}</strong>
                    <small>{{ averageResolutionLabel }}</small>
                </article>
                <article class="metric">
                    <span>Open</span>
                    <strong>{{ metrics.open }}</strong>
                    <small>{{ metrics.unassigned }} active unassigned</small>
                </article>
                <article class="metric">
                    <span>In progress</span>
                    <strong>{{ metrics.in_progress }}</strong>
                    <small>{{ metrics.resolved }} resolved</small>
                </article>
                <article class="metric danger">
                    <span>SLA breached</span>
                    <strong>{{ metrics.sla_breached }}</strong>
                    <small>{{ metrics.critical }} active critical</small>
                </article>
            </section>

            <section class="content-grid">
                <div class="panel" id="tickets">
                    <div class="panel-heading ticket-toolbar">
                        <div>
                            <p class="eyebrow">Queue</p>
                            <h2>Recent tickets</h2>
                        </div>
                        <div class="toolbar-controls">
                            <form class="search-form" @submit.prevent="loadData">
                                <input v-model="search" aria-label="Search tickets" placeholder="Search ref, subject, requester">
                                <button class="secondary" type="submit">Search</button>
                                <button v-if="search" class="link-button" type="button" @click="clearSearch">Clear</button>
                            </form>
                            <select v-model="filter" aria-label="Filter tickets by status">
                                <option value="all">All statuses</option>
                                <option value="open">Open</option>
                                <option value="in_progress">In progress</option>
                                <option value="resolved">Resolved</option>
                                <option value="closed">Closed</option>
                            </select>
                        </div>
                    </div>

                    <div v-if="loading" class="empty">Loading tickets…</div>
                    <div v-else-if="visibleTickets.length === 0" class="empty">No tickets match this filter or search.</div>
                    <div v-else class="ticket-list">
                        <article v-for="ticket in visibleTickets" :key="ticket.id" class="ticket">
                            <div class="ticket-main">
                                <div class="ticket-meta">
                                    <span>{{ ticket.reference }}</span>
                                    <span :class="['badge', `priority-${ticket.priority}`]">{{ priorityLabel(ticket.priority) }}</span>
                                    <span class="badge status">{{ statusLabel(ticket.status) }}</span>
                                    <span class="badge">{{ ticket.note_count ?? 0 }} notes</span>
                                    <span v-if="ticket.sla_breached" class="badge breach">SLA breached</span>
                                </div>
                                <h3>{{ ticket.subject }}</h3>
                                <p>{{ ticket.requester_name }} · {{ ticket.requester_email }}</p>
                            </div>
                            <div class="ticket-actions">
                                <button class="link-button" @click="openTicket(ticket.id)">View details</button>
                                <button class="link-button" @click="advanceStatus(ticket)">Advance status →</button>
                            </div>
                        </article>
                    </div>

                    <section v-if="selectedTicket" class="ticket-detail">
                        <div class="detail-heading">
                            <div>
                                <p class="eyebrow">Ticket workspace</p>
                                <h2>{{ selectedTicket.reference }} · {{ selectedTicket.subject }}</h2>
                            </div>
                            <button class="link-button" @click="selectedTicket = null">Close</button>
                        </div>

                        <div class="detail-grid">
                            <div>
                                <p class="detail-copy">{{ selectedTicket.description }}</p>
                                <dl class="detail-list">
                                    <div>
                                        <dt>Status</dt>
                                        <dd>{{ statusLabel(selectedTicket.status) }}</dd>
                                    </div>
                                    <div>
                                        <dt>Priority</dt>
                                        <dd>{{ priorityLabel(selectedTicket.priority) }}</dd>
                                    </div>
                                    <div>
                                        <dt>Requester</dt>
                                        <dd>{{ selectedTicket.requester_name }}</dd>
                                    </div>
                                    <div>
                                        <dt>Assigned to</dt>
                                        <dd>{{ selectedTicket.assignee?.name ?? 'Unassigned' }}</dd>
                                    </div>
                                </dl>
                            </div>

                            <div class="notes-panel">
                                <h3>Internal notes</h3>
                                <form class="note-form" @submit.prevent="addNote">
                                    <input v-model="noteForm.author_name" required placeholder="Author name">
                                    <textarea v-model="noteForm.body" required rows="3" placeholder="Add troubleshooting steps, findings, or handoff context."></textarea>
                                    <button class="primary" type="submit" :disabled="noteSubmitting">
                                        {{ noteSubmitting ? 'Adding…' : 'Add note' }}
                                    </button>
                                </form>

                                <div v-if="!selectedTicket.notes?.length" class="empty compact">No internal notes yet.</div>
                                <div v-else class="note-list">
                                    <article v-for="note in selectedTicket.notes" :key="note.id" class="note">
                                        <div>
                                            <strong>{{ note.author_name }}</strong>
                                            <span>{{ formatDate(note.created_at) }}</span>
                                        </div>
                                        <p>{{ note.body }}</p>
                                    </article>
                                </div>
                            </div>
                        </div>
                    </section>
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
