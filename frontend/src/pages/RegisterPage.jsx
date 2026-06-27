import { useState } from 'react';
import { useNavigate } from 'react-router-dom';
import { register } from '../api';
import { useAuth } from '../AuthContext';

export default function RegisterPage() {
  const [form, setForm]   = useState({ company: '', name: '', email: '', password: '' });
  const [error, setError] = useState('');
  const [loading, setLoading] = useState(false);
  const { signIn } = useAuth();
  const navigate = useNavigate();

  const handleSubmit = async (e) => {
    e.preventDefault();
    setError('');
    setLoading(true);
    try {
      const res = await register(form);
      signIn(res.data.token, res.data.user, res.data.organization);
      navigate('/dashboard');
    } catch (err) {
      const errors = err.response?.data?.errors;
      setError(errors ? Object.values(errors).flat().join(' ') : 'Registration failed.');
    } finally {
      setLoading(false);
    }
  };

  return (
    <div className="auth-page">
      <div className="auth-box">
        <div className="auth-logo">
          <h1>PulseDesk</h1>
          <p>Create your help desk workspace</p>
        </div>

        {error && <div className="error-msg">{error}</div>}

        <form onSubmit={handleSubmit}>
          <div className="form-group">
            <label className="form-label">Company Name</label>
            <input
              id="reg-company"
              type="text"
              className="form-input"
              placeholder="Acme Inc."
              value={form.company}
              onChange={e => setForm({...form, company: e.target.value})}
              required
            />
          </div>
          <div className="form-group">
            <label className="form-label">Your Name</label>
            <input
              id="reg-name"
              type="text"
              className="form-input"
              placeholder="John Smith"
              value={form.name}
              onChange={e => setForm({...form, name: e.target.value})}
              required
            />
          </div>
          <div className="form-group">
            <label className="form-label">Work Email</label>
            <input
              id="reg-email"
              type="email"
              className="form-input"
              placeholder="you@company.com"
              value={form.email}
              onChange={e => setForm({...form, email: e.target.value})}
              required
            />
          </div>
          <div className="form-group">
            <label className="form-label">Password</label>
            <input
              id="reg-password"
              type="password"
              className="form-input"
              placeholder="Min 6 characters"
              value={form.password}
              onChange={e => setForm({...form, password: e.target.value})}
              required
              minLength={6}
            />
          </div>
          <button id="reg-submit" type="submit" className="btn btn-primary" style={{width:'100%'}} disabled={loading}>
            {loading ? 'Creating workspace…' : 'Create Workspace →'}
          </button>
        </form>

        <div className="auth-link">
          Already have an account? <a onClick={() => navigate('/login')}>Sign in</a>
        </div>
      </div>
    </div>
  );
}
