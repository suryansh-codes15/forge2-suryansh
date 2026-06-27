import { useEffect, useState } from 'react';
import { useParams, useNavigate } from 'react-router-dom';
import { getTicket, updateTicket, getComments, createComment, getAgents } from '../api';

const STATUS_OPTIONS  = ['open', 'pending', 'resolved', 'closed'];
const PRIORITY_OPTIONS = ['low', 'medium', 'high', 'urgent'];

const STATUS_COLORS = {
  open: 'var(--green)', pending: 'var(--blue)',
  resolved: 'var(--accent)', closed: 'var(--text-faint)',
};

export default function TicketDetailPage() {
  const { id } = useParams();
  const navigate = useNavigate();

  const [ticket,  setTicket]  = useState(null);
  const [comments, setComments] = useState([]);
  const [agents,  setAgents]  = useState([]);
  const [loading, setLoading] = useState(true);
  const [commentBody, setCommentBody] = useState('');
  const [commentType, setCommentType] = useState('reply');
  const [posting, setPosting] = useState(false);
  const [error,   setError]   = useState('');

  const load = () => {
    Promise.all([getTicket(id), getComments(id), getAgents()])
      .then(([tRes, cRes, aRes]) => {
        setTicket(tRes.data);
        setComments(cRes.data);
        setAgents(aRes.data);
      })
      .catch(() => setError('Failed to load ticket.'))
      .finally(() => setLoading(false));
  };

  useEffect(() => { load(); }, [id]);

  const patch = async (fields) => {
    try {
      const res = await updateTicket(id, fields);
      setTicket(res.data);
      // Reload to get fresh activities
      getTicket(id).then(r => setTicket(r.data));
    } catch {}
  };

  const handleComment = async (e) => {
    e.preventDefault();
    if (!commentBody.trim()) return;
    setPosting(true);
    try {
      const res = await createComment(id, { body: commentBody, type: commentType });
      setComments(prev => [res.data, ...prev]);
      setCommentBody('');
    } finally {
      setPosting(false);
    }
  };

  if (loading) return (
    <>
      <div className="topbar"><h2>Ticket</h2></div>
      <div className="spinner" />
    </>
  );

  if (!ticket || error) return (
    <>
      <div className="topbar"><h2>Ticket not found</h2></div>
      <div className="page-body"><p>{error || "This ticket doesn't exist or you don't have access."}</p></div>
    </>
  );

  return (
    <>
      <div className="topbar">
        <div style={{display:'flex', alignItems:'center', gap:'12px'}}>
          <button className="btn btn-ghost btn-sm" onClick={() => navigate('/tickets')}>← Back</button>
          <h2>#{ticket.id} · {ticket.subject}</h2>
        </div>
        <div style={{display:'flex', gap:'8px', alignItems:'center'}}>
          <span className={`badge badge-${ticket.status}`}>{ticket.status}</span>
          <span className={`badge badge-${ticket.priority}`}>{ticket.priority}</span>
        </div>
      </div>

      <div className="page-body">
        <div className="ticket-detail">
          {/* Left: body + comment form + feed */}
          <div>
            {/* Original ticket description */}
            <div className="card" style={{padding:'24px', marginBottom:'20px'}}>
              <div style={{marginBottom:'12px', fontSize:'12px', color:'var(--text-muted)'}}>
                From{' '}
                <strong style={{color:'var(--text)'}}>
                  {ticket.requester?.name || 'Unknown'}
                </strong>
                {ticket.requester?.email && ` <${ticket.requester.email}>`}
                {' · '}{new Date(ticket.created_at).toLocaleString()}
              </div>
              <p style={{fontSize:'14px', lineHeight:'1.7', whiteSpace:'pre-wrap'}}>{ticket.description}</p>
            </div>

            {/* Comment form */}
            <div className="card" style={{padding:'20px', marginBottom:'20px'}}>
              <form onSubmit={handleComment}>
                <div style={{display:'flex', gap:'8px', marginBottom:'12px'}}>
                  <button
                    type="button"
                    className={`btn btn-sm ${commentType === 'reply' ? 'btn-primary' : 'btn-ghost'}`}
                    onClick={() => setCommentType('reply')}
                  >💬 Reply</button>
                  <button
                    type="button"
                    className={`btn btn-sm ${commentType === 'note' ? 'btn-primary' : 'btn-ghost'}`}
                    onClick={() => setCommentType('note')}
                  >📝 Internal Note</button>
                </div>
                <textarea
                  id="comment-body"
                  className="form-textarea"
                  placeholder={commentType === 'note' ? 'Internal note (only visible to agents)…' : 'Type your reply…'}
                  value={commentBody}
                  onChange={e => setCommentBody(e.target.value)}
                  rows={3}
                />
                <div style={{marginTop:'10px'}}>
                  <button
                    id="comment-submit"
                    type="submit"
                    className="btn btn-primary btn-sm"
                    disabled={posting || !commentBody.trim()}
                  >
                    {posting ? 'Posting…' : `Post ${commentType === 'note' ? 'Note' : 'Reply'}`}
                  </button>
                </div>
              </form>
            </div>

            {/* Comments feed */}
            {comments.length > 0 && (
              <div className="feed">
                {comments.map(c => (
                  <div key={c.id} className={`comment-item ${c.type}`}>
                    <div className="comment-author">{c.user?.name || 'System'}</div>
                    <div className="comment-meta">
                      {c.type === 'note' ? '📝 Internal Note' : '💬 Reply'}
                      {' · '}{new Date(c.created_at).toLocaleString()}
                    </div>
                    <div className="comment-body">{c.body}</div>
                  </div>
                ))}
              </div>
            )}

            {/* Activity log */}
            {ticket.activities?.length > 0 && (
              <div className="card" style={{padding:'20px', marginTop:'20px'}}>
                <h4 style={{fontSize:'13px', fontWeight:700, marginBottom:'14px'}}>Activity Log</h4>
                <div>
                  {ticket.activities.map(a => (
                    <div key={a.id} className="activity-item">
                      <div className="activity-dot" />
                      <div>
                        <strong>{a.user?.name || 'System'}</strong>
                        {' '}{a.action.replace(/_/g, ' ')}
                        {a.meta?.from && ` from ${a.meta.from}`}
                        {a.meta?.to && ` to ${a.meta.to}`}
                        <span style={{marginLeft:'6px', color:'var(--text-faint)', fontSize:'11px'}}>
                          {new Date(a.created_at).toLocaleString()}
                        </span>
                      </div>
                    </div>
                  ))}
                </div>
              </div>
            )}
          </div>

          {/* Right: meta panel */}
          <div className="ticket-meta-panel">
            <div className="meta-section">
              <h4>Status</h4>
              <select
                id="ticket-status-select"
                className="form-select"
                value={ticket.status}
                onChange={e => patch({ status: e.target.value })}
              >
                {STATUS_OPTIONS.map(s => (
                  <option key={s} value={s} style={{color: STATUS_COLORS[s]}}>{s}</option>
                ))}
              </select>
            </div>

            <div className="meta-section">
              <h4>Priority</h4>
              <select
                id="ticket-priority-select"
                className="form-select"
                value={ticket.priority}
                onChange={e => patch({ priority: e.target.value })}
              >
                {PRIORITY_OPTIONS.map(p => <option key={p} value={p}>{p}</option>)}
              </select>
            </div>

            <div className="meta-section">
              <h4>Assigned To</h4>
              <select
                id="ticket-assign-select"
                className="form-select"
                value={ticket.assigned_to ?? ''}
                onChange={e => patch({ assigned_to: e.target.value || null })}
              >
                <option value="">Unassigned</option>
                {agents.map(a => <option key={a.id} value={a.id}>{a.name}</option>)}
              </select>
            </div>

            <div className="divider" />

            <div className="meta-section">
              <h4>Requester</h4>
              <p style={{fontSize:'13px', fontWeight:600}}>{ticket.requester?.name || '—'}</p>
              <p style={{fontSize:'12px', color:'var(--text-muted)'}}>{ticket.requester?.email || ''}</p>
            </div>

            <div className="meta-section">
              <h4>Opened</h4>
              <p style={{fontSize:'12px', color:'var(--text-muted)'}}>
                {new Date(ticket.created_at).toLocaleString()}
              </p>
            </div>
          </div>
        </div>
      </div>
    </>
  );
}
