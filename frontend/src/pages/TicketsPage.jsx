import { useEffect, useState, useCallback } from 'react';
import { useNavigate } from 'react-router-dom';
import { getTickets } from '../api';

const STATUSES = ['','open','pending','resolved','closed'];
const PRIORITIES = ['','low','medium','high','urgent'];

export default function TicketsPage() {
  const [tickets, setTickets] = useState([]);
  const [loading, setLoading] = useState(true);
  const [filters, setFilters] = useState({ status: '', priority: '', search: '' });
  const [meta, setMeta] = useState({});
  const navigate = useNavigate();

  const fetchTickets = useCallback((params = {}) => {
    setLoading(true);
    getTickets({ ...filters, ...params })
      .then(res => {
        setTickets(res.data.data);
        setMeta({ total: res.data.total, page: res.data.current_page, last: res.data.last_page });
      })
      .finally(() => setLoading(false));
  }, [filters]);

  useEffect(() => { fetchTickets(); }, []);

  const handleFilter = (key, val) => {
    const newFilters = { ...filters, [key]: val };
    setFilters(newFilters);
    getTickets(newFilters).then(res => {
      setTickets(res.data.data);
      setMeta({ total: res.data.total, page: res.data.current_page, last: res.data.last_page });
    });
  };

  return (
    <>
      <div className="topbar">
        <h2>Tickets</h2>
        <button className="btn btn-primary btn-sm" onClick={() => navigate('/tickets/new')}>
          + New Ticket
        </button>
      </div>

      <div className="page-body">
        <div className="filter-bar">
          <input
            className="form-input"
            placeholder="🔍 Search subject or customer…"
            value={filters.search}
            onChange={e => handleFilter('search', e.target.value)}
          />
          <select className="form-select" value={filters.status} onChange={e => handleFilter('status', e.target.value)}>
            <option value="">All Statuses</option>
            {STATUSES.filter(Boolean).map(s => <option key={s} value={s}>{s.replace('_',' ')}</option>)}
          </select>
          <select className="form-select" value={filters.priority} onChange={e => handleFilter('priority', e.target.value)}>
            <option value="">All Priorities</option>
            {PRIORITIES.filter(Boolean).map(p => <option key={p} value={p}>{p}</option>)}
          </select>
        </div>

        <div className="card">
          {loading ? <div className="spinner" /> : (
            tickets.length === 0 ? (
              <div style={{padding:'40px', textAlign:'center', color:'var(--text-muted)'}}>
                <div style={{fontSize:'32px', marginBottom:'8px'}}>🎫</div>
                <p>No tickets found.</p>
              </div>
            ) : (
              <div className="table-wrapper">
                <table>
                  <thead>
                    <tr>
                      <th>#</th>
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
                      <tr key={ticket.id} onClick={() => navigate(`/tickets/${ticket.id}`)}>
                        <td style={{color:'var(--text-muted)', fontWeight:500}}>#{ticket.id}</td>
                        <td style={{fontWeight:600, maxWidth:'280px', overflow:'hidden', textOverflow:'ellipsis', whiteSpace:'nowrap'}}>
                          {ticket.subject}
                        </td>
                        <td>
                          <div style={{fontSize:'13px'}}>{ticket.requester?.name || '—'}</div>
                          <div style={{fontSize:'11px', color:'var(--text-muted)'}}>{ticket.requester?.email || ''}</div>
                        </td>
                        <td>
                          <span className={`badge badge-${ticket.status}`}>
                            {ticket.status.replace('_',' ')}
                          </span>
                        </td>
                        <td>
                          <span className={`badge badge-${ticket.priority}`}>{ticket.priority}</span>
                        </td>
                        <td style={{fontSize:'12px', color:'var(--text-muted)'}}>
                          {ticket.assignee?.name || <span style={{color:'var(--text-faint)'}}>Unassigned</span>}
                        </td>
                        <td style={{fontSize:'12px', color:'var(--text-muted)'}}>
                          {new Date(ticket.created_at).toLocaleDateString()}
                        </td>
                      </tr>
                    ))}
                  </tbody>
                </table>
              </div>
            )
          )}
          {meta.total > 0 && (
            <div style={{padding:'12px 16px', borderTop:'1px solid var(--border)', fontSize:'12px', color:'var(--text-muted)'}}>
              {meta.total} ticket{meta.total !== 1 ? 's' : ''} · Page {meta.page} of {meta.last}
            </div>
          )}
        </div>
      </div>
    </>
  );
}
