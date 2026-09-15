import itertools

def generate_combinations():
    numbers = [f"{i:02d}" for i in range(100)]
    
    # Pales: 2 numbers
    pales = list(itertools.combinations(numbers, 2))
    print(f"Generating pales: {len(pales)} combinations")
    with open('pales.txt', 'w', encoding='utf-8') as f:
        for p in pales:
            f.write(f"{p[0]}-{p[1]}\n")
            
    # Tripletas: 3 numbers
    tripletas = list(itertools.combinations(numbers, 3))
    print(f"Generating tripletas: {len(tripletas)} combinations")
    with open('tripletas.txt', 'w', encoding='utf-8') as f:
        for t in tripletas:
            f.write(f"{t[0]}-{t[1]}-{t[2]}\n")

if __name__ == '__main__':
    generate_combinations()
