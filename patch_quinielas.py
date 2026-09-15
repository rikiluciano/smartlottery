import re

with open('ia_quinielas.php', 'r', encoding='utf-8') as f:
    content = f.read()

# Fix date formatting
date_replacement = """                  let opt = { year: 'numeric', month: 'long', day: 'numeric' };
                  let numOpt = { day: '2-digit', month: '2-digit', year: 'numeric' };
                  let strFecha = d.toLocaleDateString('es-ES', opt);
                  let strNumFecha = d.toLocaleDateString('es-ES', numOpt);
                  document.getElementById('resFecha').innerText = strFecha + " (" + strNumFecha + ")";"""
content = re.sub(
    r"                  let opt = \{ year: 'numeric', month: 'long', day: 'numeric' \};\s+document\.getElementById\('resFecha'\)\.innerText = d\.toLocaleDateString\('es-ES', opt\);",
    date_replacement,
    content
)

# Pass pos to solicitarChatIA
content = re.sub(
    r"solicitarChatIA\(\[info\.missing\], obj === 'digito'\);",
    r"solicitarChatIA([info.missing], obj === 'digito', pos);",
    content
)
content = re.sub(
    r"solicitarChatIA\(\[info\.digit\], obj === 'digito'\);",
    r"solicitarChatIA([info.digit], obj === 'digito', pos);",
    content
)
content = re.sub(
    r"solicitarChatIA\(info\.missing, obj === 'digito'\);",
    r"solicitarChatIA(info.missing, obj === 'digito', pos);",
    content
)
content = re.sub(
    r"async function solicitarChatIA\(target, esDigito\) \{",
    r"async function solicitarChatIA(target, esDigito, pos) {",
    content
)

# Update the statsData parsing to use pos
stats_parsing_orig = """                  if (!esDigito) {
                      try {
                          if (statsData && statsData[targetParam]) {
                              let s = statsData[targetParam];
"""
stats_parsing_new = """                  if (!esDigito) {
                      try {
                          let posKey = (pos === 'primera') ? 'primera' : 'todas';
                          if (statsData && statsData[posKey] && statsData[posKey][targetParam]) {
                              let s = statsData[posKey][targetParam];
"""
content = content.replace(stats_parsing_orig, stats_parsing_new)

with open('ia_quinielas.php', 'w', encoding='utf-8') as f:
    f.write(content)
print("ia_quinielas.php parcheado correctamente.")
