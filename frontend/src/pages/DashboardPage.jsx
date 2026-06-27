import { useEffect, useState } from 'react';
import { useNavigate } from 'react-router-dom';
import { getDashboardStats } from '../api';

const STATUS_COLORS = {
  open: 'var(--green)', pending: 'var(--blue)',
  resolved: 'var(--accent)', closed: 'var(--text-faint)',
};

const PRIORITY_COLORS = {
  low: 'var(--green)', medium: 'var(--yellow)',
  high: 'var(--orange)', urgent: 'var(--red)',
};

export default function DashboardPage() {
  const [stats, setStats] = useState(null);
  const [loading, setLoading] = useState(true);
  const navigate = useNavigate();

  useEffect(() => {
    getDashboardStats()
      .then(res => setStats(res.data))
      .finally(() => setLoading(false));
  }, []);

  if (loading) return <><div className="topbar"><h2>Dashboard</h2></div><div className="spinner" /></>;

  const byStatus = stats?.by_status || {};
  const byPriority = stats?.by_priority || {};

  return (
    <>
      <div className="topbar">
        <h2>Dashboard</h2>
        <button className="btn btn-primary btn-sm" onClick={() => navigate('/tickets/new')}>
          + New Ticket
        </button>
      </div>

      <div className="page-body">
        {/* Status stat cards */}
        <div className="stats-grid">
          {[
            { key: 'total', label: 'Total', value: stats?.total || 0, color: 'var(--accent)' },
            { key: 'open', label: 'Open', value: byStatus.open || 0, color: 'var(--green)' },
            { key: 'in_progress', label: 'In Progress', value: byStatus.in_progress || 0, color: 'var(--blue)' },
            { key: 'resolved', label: 'Resolved', value: byStatus.resolved || 0, color: 'var(--accent)' },
            { key: 'closed', label: 'Closed', value: byStatus.closed || 0, color: 'var(--text-faint)' },
          ].map(s => (
            <div key={s.key} className="stat-card">
              <div className="stat-value" style={{color: s.color}}>{s.value}</div>
              <div className="stat-label">{s.label}</div>
            </div>
          ))}
        </div>

        <div style={{display:'grid', gridTemplateColumns:'1fr 1fr', gap:'20px'}}>
          {/* Priority breakdown */}
          <div className="card" style={{padding:'20px'}}>
            <h4 style={{fontSize:'13px', fontWeight:700, marginBottom:'16px'}}>By Priority</h4>
            {['urgent','high','medium','low'].map(p => (
              <div key={p} style={{display:'flex', alignItems:'center', gap:'10px', marginBottom:'10px'}}>
                <span style={{
                  width:'10px', height:'10px', borderRadius:'50%',
                  background: PRIORITY_COLORS[p], flexShrink:0
                }} />
                <span style={{flex:1, textTransform:'capitalize', fontSize:'13px'}}>{p}</span>
                <span style={{fontWeight:700, color: PRIORITY_COLORS[p]}}>{byPriority[p] || 0}</span>
              </div>
            ))}
          </div>

          {/* Agent workload */}
          <div className="card" style={{padding:'20px'}}>
            <h4 style={{fontSize:'13px', fontWeight:700, marginBottom:'16px'}}>Agent Workload</h4>
            {stats?.agent_workload?.length ? stats.agent_workload.map(agent => (
              <div key={agent.id} style={{display:'flex', alignItems:'center', gap:'10px', marginBottom:'10px'}}>
                <div style={{
                  width:28, height:28, borderRadius:'50%',
                  background:'var(--accent-glow)', border:'1px solid var(--accent)',
                  display:'flex', alignItems:'center', justifyContent:'center',
                  fontSize:'11px', fontWeight:700, color:'var(--accent)', flexShrink:0
                }}>
                  {agent.name?.[0]?.toUpperCase()}
                </div>
                <span style={{flex:1, fontSize:'13px', overflow:'hidden', textOverflow:'ellipsis', whiteSpace:'nowrap'}}>
                  {agent.name}
                </span>
                <span style={{
                  fontWeight:700, fontSize:'13px',
                  color: agent.open_tickets > 5 ? 'var(--red)' : 'var(--green)'
                }}>
                  {agent.open_tickets}
                </span>
              </div>
            )) : <p style={{color:'var(--text-muted)', fontSize:'13px'}}>No agents yet.</p>}
          </div>
        </div>

        {/* Recent tickets */}
        {stats?.recent_tickets?.length > 0 && (
          <div className="card" style={{marginTop:'20px'}}>
            <div style={{padding:'16px 20px', borderBottom:'1px solid var(--border)'}}>
              <h4 style={{fontSize:'13px', fontWeight:700}}>Recent Tickets</h4>
            </div>
            <div className="table-wrapper">
              <table>
                <thead>
                  <tr>
                    <th>#</th><th>Subject</th><th>Status</th><th>Priority</th><th>Assigned</th>
                  </tr>
                </thead>
                <tbody>
                  {stats.recent_tickets.map(t => (
                    <tr key={t.id} onClick={() => navigate(`/tickets/${t.id}`)}>
                      <td style={{color:'var(--text-muted)'}}>{t.id}</td>
                      <td style={{fontWeight:500}}>{t.subject}</td>
                      <td><span className={`badge badge-${t.status}`}>{t.status.replace('_',' ')}</span></td>
                      <td><span className={`badge badge-${t.priority}`}>{t.priority}</span></td>
                      <td style={{color:'var(--text-muted)'}}>{t.assignee?.name || '—'}</td>
                    </tr>
                  ))}
                </tbody>
              </table>
            </div>
          </div>
        )}
      </div>
    </>
  );
}
