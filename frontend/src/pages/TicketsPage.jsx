import { useEffect, useState, useCallback } from 'react';
import { useNavigate } from 'react-router-dom';
import { getTickets, getAgents } from '../api';
import { useAuth } from '../AuthContext';

const STATUSES = ['', 'open', 'pending', 'resolved', 'closed'];
const PRIORITIES = ['', 'low', 'medium', 'high', 'urgent'];

export default function TicketsPage() {
  const { user } = useAuth();
  const navigate = useNavigate();

  const [tickets, setTickets] = useState([]);
  const [agents, setAgents] = useState([]);
  const [loading, setLoading] = useState(true);
  const [filters, setFilters] = useState({ status: '', priority: '', search: '', assigned_to: '', tag: '' });
  const [tab, setTab] = useState('all'); // 'all', 'mine', 'unassigned'
  const [meta, setMeta] = useState({ total: 0, page: 1, last: 1 });

  // Load agents on mount
  useEffect(() => {
    getAgents().then(res => setAgents(res.data)).catch(() => {});
  }, []);

  const fetchTickets = useCallback((params = {}) => {
    setLoading(true);
    const activeFilters = { ...filters, ...params };
    
    // Apply tab constraint to parameters
    if (tab === 'mine' && user) {
      activeFilters.assigned_to = user.id;
    } else if (tab === 'unassigned') {
      activeFilters.assigned_to = 'unassigned';
    }

    getTickets(activeFilters)
      .then(res => {
        setTickets(res.data.data);
        setMeta({
          total: res.data.total,
          page: res.data.current_page,
          last: res.data.last_page
        });
      })
      .finally(() => setLoading(false));
  }, [filters, tab, user]);

  useEffect(() => {
    fetchTickets({ page: 1 });
  }, [tab, filters.status, filters.priority, filters.assigned_to, filters.tag]);

  const handleSearchChange = (val) => {
    setFilters(prev => ({ ...prev, search: val }));
  };

  // Trigger search on enter or button click
  const triggerSearch = () => {
    fetchTickets({ page: 1 });
  };

  const handlePageChange = (newPage) => {
    if (newPage >= 1 && newPage <= meta.last) {
      fetchTickets({ page: newPage });
    }
  };

  const exportCSV = () => {
    const headers = ['ID', 'Subject', 'Status', 'Priority', 'Requester', 'Assignee', 'Created At'];
    const rows = tickets.map(t => [
      t.id,
      t.subject.replace(/"/g, '""'),
      t.status,
      t.priority,
      t.requester?.name || '',
      t.assignee?.name || '',
      new Date(t.created_at).toLocaleString()
    ]);
    const csvContent = "data:text/csv;charset=utf-8,\uFEFF"
      + [headers.join(','), ...rows.map(e => e.map(val => `"${val}"`).join(','))].join('\n');
    const encodedUri = encodeURI(csvContent);
    const link = document.createElement("a");
    link.setAttribute("href", encodedUri);
    link.setAttribute("download", `pulsedesk_tickets_export_${Date.now()}.csv`);
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
  };

  return (
    <>
      <div className="topbar">
        <div>
          <h2>Tickets</h2>
        </div>
        <div style={{display:'flex', gap:'8px'}}>
          <button className="btn btn-ghost btn-sm" onClick={exportCSV}>
            📥 Export CSV
          </button>
          <button className="btn btn-primary btn-sm bg-accent hover:bg-opacity-90" onClick={() => navigate('/tickets/new')}>
            + New Ticket
          </button>
        </div>
      </div>

      <div className="page-body">
        {/* Quick Filter Tabs */}
        <div className="flex gap-2 mb-6 border-b border-border pb-px">
          <button
            className={`px-4 py-2 text-sm font-medium border-b-2 transition-all ${
              tab === 'all' ? 'border-accent text-accent' : 'border-transparent text-muted hover:text-text'
            }`}
            onClick={() => { setTab('all'); setFilters(f => ({ ...f, assigned_to: '' })); }}
          >
            All Tickets
          </button>
          <button
            className={`px-4 py-2 text-sm font-medium border-b-2 transition-all ${
              tab === 'mine' ? 'border-accent text-accent' : 'border-transparent text-muted hover:text-text'
            }`}
            onClick={() => setTab('mine')}
          >
            My Tickets
          </button>
          <button
            className={`px-4 py-2 text-sm font-medium border-b-2 transition-all ${
              tab === 'unassigned' ? 'border-accent text-accent' : 'border-transparent text-muted hover:text-text'
            }`}
            onClick={() => setTab('unassigned')}
          >
            Unassigned
          </button>
        </div>

        {/* Filter Toolbar */}
        <div className="flex flex-wrap gap-3 mb-5 items-center justify-between">
          <div className="flex flex-wrap gap-2.5 flex-1 max-w-4xl">
            <div className="flex gap-1 flex-1 min-w-[200px]">
              <input
                className="form-input flex-1"
                placeholder="🔍 Search subject, description, tag..."
                value={filters.search}
                onChange={e => handleSearchChange(e.target.value)}
                onKeyDown={e => e.key === 'Enter' && triggerSearch()}
              />
              <button className="btn btn-ghost btn-sm" onClick={triggerSearch}>Go</button>
            </div>
            
            <select
              className="form-select max-w-[150px]"
              value={filters.status}
              onChange={e => setFilters(f => ({ ...f, status: e.target.value }))}
            >
              <option value="">All Statuses</option>
              {STATUSES.filter(Boolean).map(s => (
                <option key={s} value={s}>{s.replace('_', ' ')}</option>
              ))}
            </select>

            <select
              className="form-select max-w-[150px]"
              value={filters.priority}
              onChange={e => setFilters(f => ({ ...f, priority: e.target.value }))}
            >
              <option value="">All Priorities</option>
              {PRIORITIES.filter(Boolean).map(p => (
                <option key={p} value={p}>{p}</option>
              ))}
            </select>

            {tab === 'all' && (
              <select
                className="form-select max-w-[180px]"
                value={filters.assigned_to}
                onChange={e => setFilters(f => ({ ...f, assigned_to: e.target.value }))}
              >
                <option value="">All Assignees</option>
                <option value="unassigned">Unassigned</option>
                {agents.map(a => (
                  <option key={a.id} value={a.id}>{a.name}</option>
                ))}
              </select>
            )}

            <input
              className="form-input max-w-[140px]"
              placeholder="Tag filter"
              value={filters.tag}
              onChange={e => setFilters(f => ({ ...f, tag: e.target.value }))}
            />
          </div>
        </div>

        {/* Tickets Table */}
        <div className="card">
          {loading ? (
            <div className="spinner" />
          ) : tickets.length === 0 ? (
            <div className="py-12 text-center text-muted">
              <div className="text-4xl mb-3">🎫</div>
              <p>No tickets found matching current filters.</p>
            </div>
          ) : (
            <div className="table-wrapper">
              <table>
                <thead>
                  <tr className="border-b border-border">
                    <th className="w-16">#</th>
                    <th>Subject</th>
                    <th>Customer</th>
                    <th>Status</th>
                    <th>Priority</th>
                    <th>Assigned</th>
                    <th>Created</th>
                  </tr>
                </thead>
                <tbody>
                  {tickets.map(ticket => (
                    <tr key={ticket.id} onClick={() => navigate(`/tickets/${ticket.id}`)} className="hover:bg-bg-input">
                      <td className="text-muted font-medium">#{ticket.id}</td>
                      <td className="max-w-xs md:max-w-sm lg:max-w-md">
                        <div className="font-semibold truncate text-text">{ticket.subject}</div>
                        {ticket.tags && ticket.tags.length > 0 && (
                          <div className="flex flex-wrap gap-1 mt-1">
                            {ticket.tags.map(t => (
                              <span key={t} className="badge-tag">{t}</span>
                            ))}
                          </div>
                        )}
                      </td>
                      <td>
                        <div className="text-sm font-medium">{ticket.requester?.name || '—'}</div>
                        <div className="text-xs text-muted">{ticket.requester?.email || ''}</div>
                      </td>
                      <td>
                        <span className={`badge badge-${ticket.status}`}>
                          {ticket.status}
                        </span>
                      </td>
                      <td>
                        <span className={`badge badge-${ticket.priority}`}>{ticket.priority}</span>
                      </td>
                      <td className="text-sm text-muted">
                        {ticket.assignee?.name || <span className="text-faint italic">Unassigned</span>}
                      </td>
                      <td className="text-xs text-muted">
                        {new Date(ticket.created_at).toLocaleDateString()}
                      </td>
                    </tr>
                  ))}
                </tbody>
              </table>
            </div>
          )}

          {/* Pagination Controls */}
          {meta.total > 0 && (
            <div className="flex items-center justify-between p-4 border-t border-border text-xs text-muted bg-bg-panel rounded-b-lg">
              <div>
                Showing <strong>{tickets.length}</strong> of <strong>{meta.total}</strong> ticket{meta.total !== 1 ? 's' : ''}
              </div>
              <div className="flex items-center gap-2">
                <button
                  className="btn btn-ghost btn-sm px-2.5 py-1"
                  onClick={() => handlePageChange(meta.page - 1)}
                  disabled={meta.page <= 1 || loading}
                >
                  ◀ Prev
                </button>
                <span>Page <strong>{meta.page}</strong> of <strong>{meta.last}</strong></span>
                <button
                  className="btn btn-ghost btn-sm px-2.5 py-1"
                  onClick={() => handlePageChange(meta.page + 1)}
                  disabled={meta.page >= meta.last || loading}
                >
                  Next ▶
                </button>
              </div>
            </div>
          )}
        </div>
      </div>
    </>
  );
}
