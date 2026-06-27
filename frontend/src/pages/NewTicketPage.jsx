import { useState, useEffect } from 'react';
import { useNavigate } from 'react-router-dom';
import { createTicket, getCustomers } from '../api';
import { useAuth } from '../AuthContext';

export default function NewTicketPage() {
  const navigate = useNavigate();
  const { user } = useAuth();
  const [customers, setCustomers] = useState([]);
  const [form, setForm] = useState({
    requester_id: '',
    subject: '',
    description: '',
    priority: 'medium',
  });
  const [error, setError]     = useState('');
  const [loading, setLoading] = useState(false);

  useEffect(() => {
    if (user && user.role !== 'customer') {
      getCustomers().then(res => setCustomers(res.data)).catch(() => {});
    }
  }, [user]);

  const handleSubmit = async (e) => {
    e.preventDefault();
    setError('');
    setLoading(true);
    try {
      const res = await createTicket({
        ...form,
        requester_id: form.requester_id || null,
      });
      navigate(`/tickets/${res.data.id}`);
    } catch (err) {
      const errors = err.response?.data?.errors;
      setError(errors ? Object.values(errors).flat().join(' ') : 'Failed to create ticket.');
    } finally {
      setLoading(false);
    }
  };

  return (
    <>
      <div className="topbar">
        <div style={{display:'flex', alignItems:'center', gap:'12px'}}>
          <button className="btn btn-ghost btn-sm" onClick={() => navigate('/tickets')}>← Back</button>
          <h2>New Ticket</h2>
        </div>
      </div>

      <div className="page-body" style={{maxWidth:'640px'}}>
        <div className="card" style={{padding:'28px'}}>
          {error && <div className="error-msg">{error}</div>}
          <form onSubmit={handleSubmit}>

            {user?.role !== 'customer' && (
              <div className="form-group">
                <label className="form-label">Requester (Customer)</label>
                <select
                  id="ticket-requester"
                  className="form-select"
                  value={form.requester_id}
                  onChange={e => setForm({...form, requester_id: e.target.value})}
                >
                  <option value="">— Select customer —</option>
                  {customers.map(c => (
                    <option key={c.id} value={c.id}>{c.name} ({c.email})</option>
                  ))}
                </select>
              </div>
            )}

            <div className="form-group">
              <label className="form-label">Subject</label>
              <input
                id="ticket-subject"
                className="form-input"
                placeholder="Brief description of the issue"
                value={form.subject}
                onChange={e => setForm({...form, subject: e.target.value})}
                required
              />
            </div>

            <div className="form-group">
              <label className="form-label">Priority</label>
              <select
                id="ticket-priority"
                className="form-select"
                value={form.priority}
                onChange={e => setForm({...form, priority: e.target.value})}
              >
                <option value="low">Low</option>
                <option value="medium">Medium</option>
                <option value="high">High</option>
                <option value="urgent">Urgent</option>
              </select>
            </div>

            <div className="form-group">
              <label className="form-label">Description</label>
              <textarea
                id="ticket-description"
                className="form-textarea"
                placeholder="Detailed description of the issue…"
                value={form.description}
                onChange={e => setForm({...form, description: e.target.value})}
                required
                rows={6}
              />
            </div>

            <div style={{display:'flex', gap:'10px'}}>
              <button id="ticket-submit" type="submit" className="btn btn-primary" disabled={loading}>
                {loading ? 'Creating…' : '🎫 Create Ticket'}
              </button>
              <button type="button" className="btn btn-ghost" onClick={() => navigate('/tickets')}>
                Cancel
              </button>
            </div>
          </form>
        </div>
      </div>
    </>
  );
}
