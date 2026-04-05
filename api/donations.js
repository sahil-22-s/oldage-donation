export default function handler(req, res) {
  res.setHeader('Access-Control-Allow-Origin', '*');
  res.setHeader('Access-Control-Allow-Methods', 'GET, POST, OPTIONS');
  res.setHeader('Access-Control-Allow-Headers', 'Content-Type');
  if (req.method === 'OPTIONS') return res.status(200).end();

  if (req.method === 'GET') {
    return res.status(200).json({ success: true, data: [], count: 0 });
  }
  if (req.method === 'POST') {
    const donation = req.body;
    return res.status(201).json({
      success: true,
      message: 'Donation recorded successfully',
      donation_id: Date.now()
    });
  }
  return res.status(405).json({ success: false, message: 'Method not allowed' });
}
