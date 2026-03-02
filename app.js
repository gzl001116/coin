const state = {
  fiatRates: {},
  market: [],
  favorites: new Set(JSON.parse(localStorage.getItem('favorites') || '[]')),
  previousPrices: new Map(),
  exchangeSeries: {},
  exchangeChart: null
};

const i18n = {
  en: {
    appTitle: 'Crypto FX Live Hub', appSubtitle: 'Real-time crypto, fiat exchange, and market dashboard.',
    converter: 'Currency Converter', marketOverview: 'Market Overview', rankings: 'Top Movers', sortBy: 'Sort by',
    sortGainers: '24h gain', sortLosers: '24h loss', sortMarketCap: 'Market cap', refresh: 'Refresh', coin: 'Coin',
    price: 'Price', change24h: '24h %', marketCap: 'Market Cap', sparkline: 'Trend', favorite: '★',
    platformRates: 'Exchange / Platform Live Rates & Fees', platform: 'Platform', makerFee: 'Maker Fee',
    takerFee: 'Taker Fee', depositFee: 'Deposit Fee', fearGreed: 'Fear & Greed Index', watchlist: 'Watchlist',
    exchangeTrend: 'Exchange BTC Price Curve', na: 'N/A'
  },
  zh: {
    appTitle: '加密货币实时汇率中心', appSubtitle: '实时加密货币、法币汇率与市场仪表盘。',
    converter: '货币转换器', marketOverview: '市场总览', rankings: '涨跌幅排行榜', sortBy: '排序',
    sortGainers: '24小时涨幅', sortLosers: '24小时跌幅', sortMarketCap: '市值', refresh: '刷新', coin: '币种',
    price: '价格', change24h: '24小时%', marketCap: '市值', sparkline: '走势', favorite: '收藏',
    platformRates: '交易所/软件实时汇率与手续费', platform: '平台', makerFee: '挂单费率', takerFee: '吃单费率',
    depositFee: '充值手续费', fearGreed: '恐惧贪婪指数', watchlist: '收藏列表', exchangeTrend: '交易所BTC价格曲线', na: '暂无'
  }
};

const lang = navigator.language.toLowerCase().startsWith('zh') ? 'zh' : 'en';
const FIAT = ['USD', 'CNY', 'EUR', 'JPY', 'HKD', 'GBP', 'AUD', 'SGD'];
const CRYPTO_IDS = ['bitcoin', 'ethereum', 'solana', 'binancecoin', 'ripple', 'dogecoin', 'cardano', 'tron', 'avalanche-2', 'chainlink', 'sui', 'toncoin'];

const EXCHANGES = [
  { name: 'Binance', maker: '0.10%', taker: '0.10%', deposit: '0-0.1%', btc: 'https://api.binance.com/api/v3/ticker/price?symbol=BTCUSDT', eth: 'https://api.binance.com/api/v3/ticker/price?symbol=ETHUSDT' },
  { name: 'Bybit', maker: '0.10%', taker: '0.10%', deposit: '0-0.1%', btc: 'https://api.bybit.com/v5/market/tickers?category=spot&symbol=BTCUSDT', eth: 'https://api.bybit.com/v5/market/tickers?category=spot&symbol=ETHUSDT' },
  { name: 'OKX', maker: '0.08%', taker: '0.10%', deposit: '0-0.1%', btc: 'https://www.okx.com/api/v5/market/ticker?instId=BTC-USDT', eth: 'https://www.okx.com/api/v5/market/ticker?instId=ETH-USDT' },
  { name: 'Kraken', maker: '0.16%', taker: '0.26%', deposit: '0.1%-0.2%', btc: 'https://api.kraken.com/0/public/Ticker?pair=XBTUSD', eth: 'https://api.kraken.com/0/public/Ticker?pair=ETHUSD' },
  { name: 'Coinbase', maker: '0.40%', taker: '0.60%', deposit: '0-0.5%', btc: 'https://api.coinbase.com/v2/prices/BTC-USD/spot', eth: 'https://api.coinbase.com/v2/prices/ETH-USD/spot' },
  { name: 'KuCoin', maker: '0.10%', taker: '0.10%', deposit: '0-0.1%', btc: 'https://api.kucoin.com/api/v1/market/orderbook/level1?symbol=BTC-USDT', eth: 'https://api.kucoin.com/api/v1/market/orderbook/level1?symbol=ETH-USDT' },
  { name: 'Gate', maker: '0.20%', taker: '0.20%', deposit: '0-0.2%', btc: 'https://api.gateio.ws/api/v4/spot/tickers?currency_pair=BTC_USDT', eth: 'https://api.gateio.ws/api/v4/spot/tickers?currency_pair=ETH_USDT' }
];

function t(key) { return i18n[lang][key] || i18n.en[key] || key; }

function applyI18n() {
  document.documentElement.lang = lang;
  document.querySelectorAll('[data-i18n]').forEach((el) => { el.textContent = t(el.dataset.i18n); });
}

async function safeFetch(url) {
  try {
    const res = await fetch(url);
    if (!res.ok) throw new Error('bad status');
    return await res.json();
  } catch {
    return null;
  }
}

async function fetchFiat() {
  const data = await safeFetch('https://api.exchangerate.host/latest?base=USD&symbols=' + FIAT.join(','));
  state.fiatRates = { USD: 1, ...(data?.rates || {}) };
}

async function fetchMarket() {
  const sort = document.getElementById('sortSelect').value;
  const url = `https://api.coingecko.com/api/v3/coins/markets?vs_currency=usd&ids=${CRYPTO_IDS.join(',')}&order=${sort}&sparkline=true&price_change_percentage=24h`;
  const data = await safeFetch(url);
  state.market = Array.isArray(data) ? data : [];
}

async function fetchFearGreed() {
  const data = await safeFetch('https://api.alternative.me/fng/?limit=1');
  return Number(data?.data?.[0]?.value || 50);
}

function parseTicker(name, data) {
  if (!data) return null;
  if (name === 'Binance') return Number(data.price);
  if (name === 'Bybit') return Number(data.result?.list?.[0]?.lastPrice);
  if (name === 'OKX') return Number(data.data?.[0]?.last);
  if (name === 'Kraken') {
    const pair = Object.keys(data.result || {})[0];
    return Number(data.result?.[pair]?.c?.[0]);
  }
  if (name === 'Coinbase') return Number(data.data?.amount);
  if (name === 'KuCoin') return Number(data.data?.price);
  if (name === 'Gate') return Number(data?.[0]?.last);
  return null;
}

function pushExchangePoint(exchange, value) {
  if (!Number.isFinite(value)) return;
  if (!state.exchangeSeries[exchange]) state.exchangeSeries[exchange] = [];
  state.exchangeSeries[exchange].push(value);
  if (state.exchangeSeries[exchange].length > 24) state.exchangeSeries[exchange].shift();
}

async function fetchPlatformRates() {
  const rows = await Promise.all(EXCHANGES.map(async (p) => {
    const [b, e] = await Promise.all([safeFetch(p.btc), safeFetch(p.eth)]);
    const btcV = parseTicker(p.name, b);
    const ethV = parseTicker(p.name, e);
    pushExchangePoint(p.name, btcV);
    return { ...p, btcV, ethV };
  }));

  const tbody = document.getElementById('platformTableBody');
  tbody.innerHTML = rows.map((r) => `
    <tr>
      <td>${r.name}</td>
      <td>${Number.isFinite(r.btcV) ? r.btcV.toFixed(2) : '--'}</td>
      <td>${Number.isFinite(r.ethV) ? r.ethV.toFixed(2) : '--'}</td>
      <td>${r.maker}</td>
      <td>${r.taker}</td>
      <td>${r.deposit}</td>
    </tr>
  `).join('');

  renderExchangeChart();
}

function formatUsd(v) { return Number.isFinite(v) ? `$${v.toLocaleString(undefined, { maximumFractionDigits: 2 })}` : '--'; }

function renderConverter() {
  const from = document.getElementById('fromSelect');
  const to = document.getElementById('toSelect');
  const cryptoSymbols = state.market.map((c) => c.symbol.toUpperCase());
  const options = [...FIAT, ...cryptoSymbols];
  const fromOld = from.value || 'USD';
  const toOld = to.value || 'CNY';

  from.innerHTML = options.map((o) => `<option value="${o}">${o}</option>`).join('');
  to.innerHTML = options.map((o) => `<option value="${o}">${o}</option>`).join('');
  from.value = options.includes(fromOld) ? fromOld : 'USD';
  to.value = options.includes(toOld) ? toOld : 'CNY';

  const recalc = () => {
    const amount = Number(document.getElementById('amountInput').value || 0);
    const v = convert(amount, from.value, to.value);
    document.getElementById('conversionResult').textContent = Number.isFinite(v)
      ? `${amount} ${from.value} = ${v.toFixed(6)} ${to.value}`
      : t('na');
  };

  [from, to, document.getElementById('amountInput')].forEach((el) => {
    el.oninput = recalc;
  });
  recalc();
}

function unitToUsd(unit) {
  if (state.fiatRates[unit]) return 1 / state.fiatRates[unit];
  const coin = state.market.find((c) => c.symbol.toUpperCase() === unit);
  return coin ? coin.current_price : NaN;
}

function convert(amount, from, to) {
  const fromInUsd = unitToUsd(from);
  const toInUsd = unitToUsd(to);
  return (amount * fromInUsd) / toInUsd;
}

function renderMarketOverview() {
  const totalCap = state.market.reduce((sum, c) => sum + (c.market_cap || 0), 0);
  const avgChange = state.market.reduce((s, c) => s + (c.price_change_percentage_24h || 0), 0) / (state.market.length || 1);
  const btc = state.market.find((c) => c.id === 'bitcoin');
  const btcDom = totalCap ? ((btc?.market_cap || 0) / totalCap) * 100 : 0;
  const cards = [
    ['Total Market Cap', `$${Math.round(totalCap).toLocaleString()}`],
    ['Avg 24h Move', `${avgChange.toFixed(2)}%`],
    ['BTC Dominance', `${btcDom.toFixed(2)}%`],
    ['Tracked Assets', `${state.market.length}`]
  ];
  document.getElementById('marketOverviewGrid').innerHTML = cards
    .map(([label, value]) => `<div class="kpi"><div class="label">${label}</div><div>${value}</div></div>`)
    .join('');
}

function sparklineSvg(points) {
  const list = points?.slice(-30) || [];
  if (!list.length) return '--';
  const min = Math.min(...list);
  const max = Math.max(...list);
  const range = max - min || 1;
  const path = list.map((v, i) => `${(i / (list.length - 1 || 1)) * 100},${30 - ((v - min) / range) * 30}`).join(' ');
  return `<svg class="spark" viewBox="0 0 100 30" preserveAspectRatio="none"><polyline points="${path}" /></svg>`;
}

function renderTable() {
  const body = document.getElementById('coinTableBody');
  body.innerHTML = state.market.map((c) => {
    const chg = c.price_change_percentage_24h || 0;
    const cls = chg >= 0 ? 'pos' : 'neg';
    const prev = state.previousPrices.get(c.id);
    const flash = prev ? (c.current_price > prev ? 'flash-up' : c.current_price < prev ? 'flash-down' : '') : '';
    state.previousPrices.set(c.id, c.current_price);
    return `
      <tr class="${flash}">
        <td><a class="coin-link" href="detail.html?id=${c.id}">${c.name} (${c.symbol.toUpperCase()})</a></td>
        <td>${formatUsd(c.current_price)}</td>
        <td class="${cls}">${chg.toFixed(2)}%</td>
        <td>$${Math.round(c.market_cap || 0).toLocaleString()}</td>
        <td>${sparklineSvg(c.sparkline_in_7d?.price)}</td>
        <td><button class="btn fav-btn" data-id="${c.id}">${state.favorites.has(c.id) ? '★' : '☆'}</button></td>
      </tr>`;
  }).join('');

  body.querySelectorAll('.fav-btn').forEach((btn) => {
    btn.addEventListener('click', () => {
      const id = btn.dataset.id;
      if (state.favorites.has(id)) state.favorites.delete(id); else state.favorites.add(id);
      localStorage.setItem('favorites', JSON.stringify([...state.favorites]));
      renderTable();
      renderWatchlist();
    });
  });
}

function renderWatchlist() {
  const favs = state.market.filter((c) => state.favorites.has(c.id));
  document.getElementById('watchlist').innerHTML = favs.length
    ? favs.map((c) => `<li>${c.name}: ${formatUsd(c.current_price)} (${(c.price_change_percentage_24h || 0).toFixed(2)}%)</li>`).join('')
    : '<li>--</li>';
}

function drawFearGreed(value) {
  const c = document.getElementById('fearGreedGauge');
  const ctx = c.getContext('2d');
  ctx.clearRect(0, 0, c.width, c.height);
  ctx.lineWidth = 18;
  ctx.beginPath();
  ctx.strokeStyle = '#2f375f';
  ctx.arc(120, 120, 80, Math.PI, 0);
  ctx.stroke();
  ctx.beginPath();
  ctx.strokeStyle = value > 60 ? '#1ed985' : value < 40 ? '#ff5d73' : '#ffbf3f';
  ctx.arc(120, 120, 80, Math.PI, Math.PI + (value / 100) * Math.PI);
  ctx.stroke();
  document.getElementById('fearGreedText').textContent = `${value}/100`;
}

function renderExchangeChart() {
  const labelsCount = Math.max(...Object.values(state.exchangeSeries).map((arr) => arr.length), 0);
  if (!labelsCount) return;
  const labels = Array.from({ length: labelsCount }, (_, i) => `${i + 1}`);
  const palette = ['#5f7dff', '#1ed985', '#ff5d73', '#f8c537', '#8e6cff', '#00c2ff', '#ff8f1f'];
  const datasets = Object.entries(state.exchangeSeries).map(([name, data], i) => ({
    label: name,
    data,
    borderColor: palette[i % palette.length],
    pointRadius: 0,
    tension: 0.25
  }));

  const ctx = document.getElementById('exchangeChart').getContext('2d');
  if (state.exchangeChart) {
    state.exchangeChart.data.labels = labels;
    state.exchangeChart.data.datasets = datasets;
    state.exchangeChart.update();
  } else {
    state.exchangeChart = new Chart(ctx, {
      type: 'line',
      data: { labels, datasets },
      options: { responsive: true, plugins: { legend: { labels: { color: '#e8ecff' } } }, scales: { x: { display: false }, y: { ticks: { color: '#98a3c7' } } } }
    });
  }
}

function updateFavicon() {
  const btc = state.market.find((c) => c.id === 'bitcoin');
  if (!btc) return;
  const canvas = document.createElement('canvas');
  canvas.width = 64;
  canvas.height = 64;
  const ctx = canvas.getContext('2d');
  ctx.fillStyle = '#111';
  ctx.fillRect(0, 0, 64, 64);
  ctx.fillStyle = '#f7931a';
  ctx.font = 'bold 16px sans-serif';
  ctx.fillText(`$${Math.round(btc.current_price / 1000)}k`, 6, 38);
  let link = document.querySelector("link[rel*='icon']");
  if (!link) {
    link = document.createElement('link');
    link.rel = 'icon';
    document.head.appendChild(link);
  }
  link.href = canvas.toDataURL('image/png');
}

async function refreshAll() {
  await Promise.all([fetchFiat(), fetchMarket(), fetchPlatformRates()]);
  renderConverter();
  renderMarketOverview();
  renderTable();
  renderWatchlist();
  updateFavicon();
  drawFearGreed(await fetchFearGreed());
  document.getElementById('lastUpdate').textContent = new Date().toLocaleTimeString();
}

applyI18n();
refreshAll();
setInterval(refreshAll, 15000);
document.getElementById('sortSelect').addEventListener('change', refreshAll);
document.getElementById('refreshBtn').addEventListener('click', refreshAll);
