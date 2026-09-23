from playwright.sync_api import sync_playwright

def test_index_page_loads():
    with sync_playwright() as p:
        browser = p.chromium.launch(headless=True)
        page = browser.new_page()
        page.goto("http://localhost:8080/index.php")
        
        # Verify the title contains something expected or header exists
        assert page.title() != "", "La página debería tener un título"
        
        # Verificar que existen las tarjetas de resultados o el contenedor principal
        # The page uses .grid or .cards classes based on components/card.php or index.php
        # Let's check for standard elements we expect to see
        content = page.content()
        assert "Lottery" in content or "Lotería" in content or "Resultados" in content or "quiniela" in content.lower(), "Debe contener texto de lotería"
        
        # Optionally take a screenshot to prove E2E works
        page.screenshot(path="e2e_screenshot.png")
        
        browser.close()
        print("✅ E2E Test Passed: La página principal carga correctamente.")

if __name__ == "__main__":
    test_index_page_loads()
