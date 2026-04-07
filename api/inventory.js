const defaultItems = [
  { id: 1, item_id: "ITEM-001", name: "Wheelchairs", description: "Comfortable mobility wheelchairs for residents", stock_quantity: 5, image_url: "https://images.unsplash.com/photo-1587745416684-47a6b380635b?w=400&q=80" },
  { id: 2, item_id: "ITEM-002", name: "Medicines", description: "Essential medical supplies and vitamins", stock_quantity: 20, image_url: "https://images.unsplash.com/photo-1587854692152-cbe660dbde0f?w=400&q=80" },
  { id: 3, item_id: "ITEM-003", name: "Walking Aids", description: "Canes, walkers, and balance support equipment", stock_quantity: 8, image_url: "https://images.unsplash.com/photo-1576091160550-2173dba999ef?w=400&q=80" },
  { id: 4, item_id: "ITEM-004", name: "Food & Nutrition", description: "Healthy meals and nutritional supplements", stock_quantity: 15, image_url: "https://images.unsplash.com/photo-1546069901-ba9599a7e63c?w=400&q=80" },
  { id: 5, item_id: "ITEM-005", name: "Bedding & Comfort", description: "Quality pillows, blankets, and mattresses", stock_quantity: 12, image_url: "https://images.unsplash.com/photo-1586788944171-a1beba7f5a6b?w=400&q=80" },
  { id: 6, item_id: "ITEM-006", name: "Entertainment", description: "Books, games, and activity materials", stock_quantity: 10, image_url: "https://images.unsplash.com/photo-1507842217343-583f20270319?w=400&q=80" }
];

export default function handler(req, res) {
  res.setHeader('Access-Control-Allow-Origin', '*');
  res.setHeader('Access-Control-Allow-Methods', 'GET, POST, PUT, DELETE, OPTIONS');
  res.setHeader('Access-Control-Allow-Headers', 'Content-Type');
  if (req.method === 'OPTIONS') return res.status(200).end();

  if (req.method === 'GET') {
    return res.status(200).json({ success: true, data: defaultItems, count: defaultItems.length });
  }
  if (req.method === 'POST') {
    const item = req.body;
    return res.status(201).json({ success: true, message: 'Item added successfully', item_id: item.item_id || ('ITEM-' + Date.now()), id: Date.now() });
  }
  if (req.method === 'PUT') {
    return res.status(200).json({ success: true, message: 'Item updated successfully' });
  }
  if (req.method === 'DELETE') {
    return res.status(200).json({ success: true, message: 'Item deleted successfully' });
  }
  return res.status(405).json({ success: false, message: 'Method not allowed' });
}
