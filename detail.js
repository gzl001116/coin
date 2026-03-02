const params = new URLSearchParams(location.search);
const coinId = params.get('id') || 'bitcoin';

async function loadDetail() {
  const [detailRes, chartRes] = await Promise.all([
    fetch(`https://api.coingecko.com/api/v3/coins/${coinId}`),
    fetch(`https://api.coingecko.com/api/v3/coins/${coinId}/market_chart?vs_currency=usd&days=7&interval=hourly`)
  ]);
  const detail = await detailRes.json();
  const chart = await chartRes.json();

  document.getElementById('detailTitle').textContent = `${detail.name} (${detail.symbol.toUpperCase()})`;
  document.getElementById('detailDescription').innerHTML = (detail.description?.en || 'No description').slice(0, 700) + '...';

  const stats = [
    ['Current price', `$${detail.market_data.current_price.usd}`],
    ['24h high / low', `$${detail.market_data.high_24h.usd} / $${detail.market_data.low_24h.usd}`],
    ['Market cap', `$${Math.round(detail.market_data.market_cap.usd).toLocaleString()}`],
    ['Circulating supply', `${Math.round(detail.market_data.circulating_supply).toLocaleString()}`],
    ['Genesis date', detail.genesis_date || '--']
  ];

  document.getElementById('detailStats').innerHTML = stats.map(([k,v]) => `<li><strong>${k}:</strong> ${v}</li>`).join('');

  const labels = chart.prices.map(p => new Date(p[0]).toLocaleString());
  const values = chart.prices.map(p => p[1]);

  new Chart(document.getElementById('detailChart').getContext('2d'), {
    type: 'line',
    data: { labels, datasets: [{ label: `${detail.symbol.toUpperCase()} USD`, data: values, borderColor: '#5f7dff', tension: 0.2, pointRadius: 0 }] },
    options: { responsive: true, plugins: { legend: { display: true } }, scales: { x: { display: false } } }
  });
}

loadDetail();
