export type TicketPriority = 'low' | 'medium' | 'high' | 'critical';
export type TicketStatus = 'open' | 'in_progress' | 'resolved' | 'closed';

export interface TicketNote {
    id: number;
    author_name: string;
    body: string;
    created_at: string;
}

export interface Ticket {
    id: number;
    reference: string;
    requester_name: string;
    requester_email: string;
    subject: string;
    description: string;
    priority: TicketPriority;
    status: TicketStatus;
    assignee?: { id: number; name: string } | null;
    note_count?: number;
    notes?: TicketNote[];
    due_at: string | null;
    resolved_at: string | null;
    sla_breached: boolean;
    created_at: string;
    updated_at?: string;
}

export interface DashboardMetrics {
    total: number;
    open: number;
    in_progress: number;
    resolved: number;
    critical: number;
    unassigned: number;
    sla_breached: number;
    average_resolution_minutes: number | null;
}
