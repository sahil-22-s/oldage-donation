export default function handler(req, res) {
  res.setHeader('Access-Control-Allow-Origin', '*');
  res.setHeader('Access-Control-Allow-Methods', 'POST, OPTIONS');
  res.setHeader('Access-Control-Allow-Headers', 'Content-Type');
  if (req.method === 'OPTIONS') return res.status(200).end();

  if (req.method === 'POST') {
    const { username, password } = req.body || {};
    if (username === 'admin' && password === '1234') {
      return res.status(200).json({ success: true, message: 'Login successful', admin_id: 1 });
    }
    return res.status(401).json({ success: false, message: 'Invalid credentials' });
  }
  return res.status(405).json({ success: false, message: 'Method not allowed' });
}
