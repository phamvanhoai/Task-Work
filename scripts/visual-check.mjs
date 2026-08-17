import { chromium } from 'playwright-core';

const browser = await chromium.launch({ executablePath: 'C:/Program Files (x86)/Microsoft/Edge/Application/msedge.exe' });
const page = await browser.newPage({ viewport: { width: 1536, height: 1024 }, deviceScaleFactor: 1 });
await page.goto('http://127.0.0.1:8000/login');
await page.locator('input[name="email"]').fill('admin@taskwork.local');
await page.locator('input[name="password"]').fill('ChangeMe123!');
await Promise.all([page.waitForURL('**/dashboard'), page.locator('.login-submit').click()]);
for (const [name, path] of Object.entries({ dashboard: '/dashboard', 'task-cua-toi': '/my-tasks', 'tat-ca-task': '/tasks', 'du-an': '/projects', lich: '/calendar', 'bao-cao': '/reports', 'thanh-vien': '/members', nhan: '/labels', 'cai-dat': '/settings' })) {
    await page.goto(`http://127.0.0.1:8000${path}`);
    await page.screenshot({ path: `storage/app/${name}-current.png`, fullPage: false });
}
await browser.close();
