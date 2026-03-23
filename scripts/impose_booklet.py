#!/usr/bin/env python3
"""
Booklet Imposition Script voor PrintMijnPDF
Werkt met pikepdf 10.x+
"""

import argparse
import sys
from pathlib import Path

try:
    import pikepdf
except ImportError:
    print("Error: pikepdf niet geinstalleerd", file=sys.stderr)
    sys.exit(1)

# Sheet formaten in punten (72 punten = 1 inch)
SHEET_FORMATS = {
    'SRA3': {'width': 907, 'height': 1276},
    'SRA4': {'width': 638, 'height': 907},
}

def calculate_booklet_pages(num_pages):
    """Bereken de paginavolgorde voor saddle-stitch binding."""
    total_pages = ((num_pages + 3) // 4) * 4
    sheets = []
    num_sheets = total_pages // 4
    
    for sheet_num in range(num_sheets):
        outer_left = total_pages - (sheet_num * 2)
        outer_right = (sheet_num * 2) + 1
        inner_left = (sheet_num * 2) + 2
        inner_right = total_pages - (sheet_num * 2) - 1
        
        sheets.append({
            'front': (outer_left, outer_right),
            'back': (inner_left, inner_right)
        })
    
    return sheets, total_pages

def impose_booklet(input_path, output_path, source_format='A4', quiet=False):
    """Maak booklet impositie van een PDF."""
    
    input_path = Path(input_path)
    output_path = Path(output_path)
    
    if not input_path.exists():
        print(f"Error: Input bestand niet gevonden: {input_path}", file=sys.stderr)
        return False
    
    sheet_format = 'SRA4' if source_format == 'A5' else 'SRA3'
    sheet = SHEET_FORMATS[sheet_format]
    
    try:
        with pikepdf.open(input_path) as pdf:
            num_pages = len(pdf.pages)
            
            if num_pages == 0:
                print("Error: PDF heeft geen paginas", file=sys.stderr)
                return False
            
            sheets, total_pages = calculate_booklet_pages(num_pages)
            
            if not quiet:
                print(f"Input: {num_pages} pagina's")
                print(f"Output: {len(sheets)} vellen ({sheet_format})")
            
            output_pdf = pikepdf.new()
            half_width = sheet['width'] / 2
            
            for sheet_data in sheets:
                # Voorkant
                front = create_sheet_page(output_pdf, sheet, pdf, sheet_data['front'], num_pages, half_width)
                output_pdf.pages.append(front)
                
                # Achterkant
                back = create_sheet_page(output_pdf, sheet, pdf, sheet_data['back'], num_pages, half_width)
                output_pdf.pages.append(back)
            
            output_path.parent.mkdir(parents=True, exist_ok=True)
            output_pdf.save(output_path)
            
            if not quiet:
                print(f"Output opgeslagen: {output_path}")
            
            return True
            
    except Exception as e:
        print(f"Error: {e}", file=sys.stderr)
        import traceback
        traceback.print_exc()
        return False

def create_sheet_page(output_pdf, sheet, source_pdf, page_nums, num_pages, half_width):
    """Maak een sheet pagina met twee bronpagina's naast elkaar."""
    
    left_num, right_num = page_nums
    
    # Start met lege pagina
    sheet_page = pikepdf.Page(pikepdf.Dictionary(
        Type=pikepdf.Name.Page,
        MediaBox=[0, 0, sheet['width'], sheet['height']],
        Resources=pikepdf.Dictionary(XObject=pikepdf.Dictionary()),
        Contents=pikepdf.Stream(output_pdf, b"")
    ))
    
    content_parts = []
    xobject_counter = 0
    
    # Linker pagina
    if 1 <= left_num <= num_pages:
        xobject_counter += 1
        name = f"P{xobject_counter}"
        place_page(output_pdf, sheet_page, source_pdf.pages[left_num - 1], name, 0, 0, half_width, sheet['height'])
        content_parts.append(f"q 1 0 0 1 0 0 cm /{name} Do Q")
    
    # Rechter pagina
    if 1 <= right_num <= num_pages:
        xobject_counter += 1
        name = f"P{xobject_counter}"
        place_page(output_pdf, sheet_page, source_pdf.pages[right_num - 1], name, half_width, 0, half_width, sheet['height'])
        content_parts.append(f"q 1 0 0 1 0 0 cm /{name} Do Q")
    
    return sheet_page

def get_page_content_bytes(page):
    """Lees content bytes van een pagina, ook als Contents een array is."""
    if page.Contents is None:
        return b""
    contents = page.Contents
    if isinstance(contents, pikepdf.Array):
        parts = []
        for stream in contents:
            parts.append(stream.read_bytes())
        return b"\n".join(parts)
    return contents.read_bytes()

def place_page(output_pdf, sheet_page, source_page, name, x, y, target_w, target_h):
    """Plaats een pagina als Form XObject op de sheet."""

    # Haal source dimensies
    mb = source_page.mediabox
    src_w = float(mb[2]) - float(mb[0])
    src_h = float(mb[3]) - float(mb[1])

    # Bereken schaal
    scale = min(target_w / src_w, target_h / src_h) * 0.95

    # Centreer
    scaled_w = src_w * scale
    scaled_h = src_h * scale
    x_off = x + (target_w - scaled_w) / 2
    y_off = y + (target_h - scaled_h) / 2

    # Kopieer pagina als form xobject
    content_bytes = get_page_content_bytes(source_page)
    form = output_pdf.make_stream(content_bytes)
    form.stream_dict[pikepdf.Name.Type] = pikepdf.Name.XObject
    form.stream_dict[pikepdf.Name.Subtype] = pikepdf.Name.Form
    form.stream_dict[pikepdf.Name.BBox] = source_page.mediabox

    # Kopieer resources (verplicht voor fonts/afbeeldingen)
    if '/Resources' in source_page:
        form.stream_dict[pikepdf.Name.Resources] = output_pdf.copy_foreign(source_page.Resources)
    else:
        form.stream_dict[pikepdf.Name.Resources] = pikepdf.Dictionary()

    # Voeg toe aan sheet
    sheet_page.Resources.XObject[pikepdf.Name(name)] = form

    # Update content stream
    new_content = f"q {scale:.4f} 0 0 {scale:.4f} {x_off:.2f} {y_off:.2f} cm /{name} Do Q\n"
    existing = sheet_page.Contents.read_bytes() if sheet_page.Contents else b""
    sheet_page.Contents = pikepdf.Stream(output_pdf, existing + new_content.encode())

def main():
    parser = argparse.ArgumentParser(description='Booklet Imposition')
    parser.add_argument('input', help='Input PDF')
    parser.add_argument('output', help='Output PDF')
    parser.add_argument('--format', choices=['A4', 'A5'], default='A4')
    parser.add_argument('--quiet', '-q', action='store_true')
    
    args = parser.parse_args()
    success = impose_booklet(args.input, args.output, args.format, args.quiet)
    sys.exit(0 if success else 1)

if __name__ == '__main__':
    main()
