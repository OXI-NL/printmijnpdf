#!/usr/bin/env python3
"""
Saddle Stitch Booklet Imposition Script voor PrintMijnPDF.nl

Dit script neemt een PDF en maakt er een print-ready impositie van voor
geniet gebrocheerde boekjes. Features:

- Detecteert TrimBox en snijdt indien nodig naar TrimBox
- Vult aan tot pagina's deelbaar door 4 (blanco pagina's)
- Maakt saddle stitch impositie (reader spreads naar printer spreads)
- Voegt snijtekens en vouwlijn toe
- Output op SRA3 (A4 boekje) of SRA4 (A5 boekje)

Gebruik:
    python impose_booklet.py input.pdf output.pdf [--format A4|A5]
"""

import argparse
import sys
from pathlib import Path
from typing import Tuple, List
import pikepdf
from pikepdf import Pdf, Page, Rectangle
from reportlab.pdfgen import canvas
from reportlab.lib.units import mm
from reportlab.lib.colors import black, white
from io import BytesIO
import tempfile
import os


# Constanten (in punten, 1 punt = 1/72 inch)
MM_TO_PT = 72 / 25.4

# Paginaformaten
FORMATS = {
    'A4': {
        'page_width': 210 * MM_TO_PT,
        'page_height': 297 * MM_TO_PT,
        'sheet_width': 450 * MM_TO_PT,   # SRA3 breedte
        'sheet_height': 320 * MM_TO_PT,  # SRA3 hoogte
        'name': 'A4 op SRA3'
    },
    'A5': {
        'page_width': 148 * MM_TO_PT,
        'page_height': 210 * MM_TO_PT,
        'sheet_width': 320 * MM_TO_PT,   # SRA4 breedte  
        'sheet_height': 225 * MM_TO_PT,  # SRA4 hoogte
        'name': 'A5 op SRA4'
    }
}

# Snijtekens instellingen
CROP_MARK_LENGTH = 5 * MM_TO_PT
CROP_MARK_OFFSET = 3 * MM_TO_PT  # Afstand vanaf trimbox
CROP_MARK_WEIGHT = 0.25  # Lijndikte in punten


def get_page_dimensions(page: Page) -> Tuple[float, float, Rectangle]:
    """
    Bepaal de effectieve pagina-afmetingen.
    Geeft (width, height, box) terug.
    
    Prioriteit: TrimBox > CropBox > MediaBox
    """
    # Haal de verschillende boxes op
    mediabox = page.mediabox
    
    # Probeer TrimBox (beste voor print)
    try:
        trimbox = page.trimbox
        if trimbox and trimbox != mediabox:
            width = float(trimbox[2]) - float(trimbox[0])
            height = float(trimbox[3]) - float(trimbox[1])
            return width, height, trimbox
    except (KeyError, AttributeError):
        pass
    
    # Probeer CropBox
    try:
        cropbox = page.cropbox
        if cropbox and cropbox != mediabox:
            width = float(cropbox[2]) - float(cropbox[0])
            height = float(cropbox[3]) - float(cropbox[1])
            return width, height, cropbox
    except (KeyError, AttributeError):
        pass
    
    # Fallback naar MediaBox
    width = float(mediabox[2]) - float(mediabox[0])
    height = float(mediabox[3]) - float(mediabox[1])
    return width, height, mediabox


def detect_format(width: float, height: float) -> str:
    """Detecteer of het A4 of A5 is gebaseerd op afmetingen."""
    # A4: 210x297mm, A5: 148x210mm
    a4_w, a4_h = 210 * MM_TO_PT, 297 * MM_TO_PT
    a5_w, a5_h = 148 * MM_TO_PT, 210 * MM_TO_PT
    
    # Tolerantie van 5mm
    tolerance = 5 * MM_TO_PT
    
    # Check A4 (portrait of landscape)
    if (abs(width - a4_w) < tolerance and abs(height - a4_h) < tolerance) or \
       (abs(width - a4_h) < tolerance and abs(height - a4_w) < tolerance):
        return 'A4'
    
    # Check A5 (portrait of landscape)
    if (abs(width - a5_w) < tolerance and abs(height - a5_h) < tolerance) or \
       (abs(width - a5_h) < tolerance and abs(height - a5_w) < tolerance):
        return 'A5'
    
    # Default naar A4 als onduidelijk
    return 'A4'


def calculate_imposition_order(page_count: int) -> List[Tuple[int, int]]:
    """
    Bereken de impositie volgorde voor saddle stitch.
    
    Voor een 8-pagina boekje (2 vellen):
    Vel 1 voorzijde: (8, 1)  - laatste + eerste
    Vel 1 achterzijde: (2, 7)
    Vel 2 voorzijde: (6, 3)
    Vel 2 achterzijde: (4, 5)
    
    Returns: List van tuples (linker_pagina, rechter_pagina)
             Pagina nummers zijn 1-indexed
    """
    spreads = []
    sheets = page_count // 4
    
    for sheet in range(sheets):
        # Elk vel heeft 4 pagina's (2 spreads: voor en achter)
        # Voorzijde: buitenste pagina's
        front_left = page_count - (sheet * 2)
        front_right = 1 + (sheet * 2)
        
        # Achterzijde: binnenste pagina's  
        back_left = 2 + (sheet * 2)
        back_right = page_count - 1 - (sheet * 2)
        
        spreads.append((front_left, front_right))  # Voorzijde
        spreads.append((back_left, back_right))    # Achterzijde
    
    return spreads


def create_crop_marks_overlay(
    sheet_width: float,
    sheet_height: float,
    page_width: float,
    page_height: float
) -> bytes:
    """
    Maak een PDF overlay met snijtekens en vouwlijn.
    """
    buffer = BytesIO()
    c = canvas.Canvas(buffer, pagesize=(sheet_width, sheet_height))
    
    # Bereken posities
    center_x = sheet_width / 2
    center_y = sheet_height / 2
    
    # Linker pagina positie
    left_x = center_x - page_width
    # Rechter pagina positie  
    right_x = center_x
    
    # Y posities (gecentreerd)
    bottom_y = center_y - (page_height / 2)
    top_y = center_y + (page_height / 2)
    
    c.setStrokeColor(black)
    c.setLineWidth(CROP_MARK_WEIGHT)
    
    # === SNIJTEKENS ===
    
    # Hoekpunten waar snijtekens moeten komen
    corners = [
        # Linker pagina
        (left_x, bottom_y),      # Links onder
        (left_x, top_y),         # Links boven
        # Midden (vouwlijn)
        (center_x, bottom_y),    # Midden onder
        (center_x, top_y),       # Midden boven
        # Rechter pagina
        (right_x + page_width, bottom_y),  # Rechts onder
        (right_x + page_width, top_y),     # Rechts boven
    ]
    
    # Teken snijtekens bij elke hoek
    for x, y in corners:
        # Horizontale lijnen
        # Links van het punt
        c.line(x - CROP_MARK_OFFSET - CROP_MARK_LENGTH, y,
               x - CROP_MARK_OFFSET, y)
        # Rechts van het punt
        c.line(x + CROP_MARK_OFFSET, y,
               x + CROP_MARK_OFFSET + CROP_MARK_LENGTH, y)
        
        # Verticale lijnen
        # Onder het punt
        c.line(x, y - CROP_MARK_OFFSET - CROP_MARK_LENGTH,
               x, y - CROP_MARK_OFFSET)
        # Boven het punt
        c.line(x, y + CROP_MARK_OFFSET,
               x, y + CROP_MARK_OFFSET + CROP_MARK_LENGTH)
    
    # === VOUWLIJN (stippellijn in het midden) ===
    c.setDash(3, 3)  # Stippellijn: 3pt aan, 3pt uit
    c.setStrokeColor(black)
    
    # Vouwlijn boven de pagina
    c.line(center_x, top_y + CROP_MARK_OFFSET,
           center_x, top_y + CROP_MARK_OFFSET + CROP_MARK_LENGTH * 2)
    
    # Vouwlijn onder de pagina  
    c.line(center_x, bottom_y - CROP_MARK_OFFSET,
           center_x, bottom_y - CROP_MARK_OFFSET - CROP_MARK_LENGTH * 2)
    
    c.save()
    buffer.seek(0)
    return buffer.getvalue()


def impose_booklet(
    input_path: str,
    output_path: str,
    format_override: str = None,
    verbose: bool = True
) -> dict:
    """
    Hoofdfunctie voor boekje impositie.
    
    Args:
        input_path: Pad naar input PDF
        output_path: Pad naar output PDF
        format_override: 'A4' of 'A5' om formaat te forceren
        verbose: Print status berichten
    
    Returns:
        dict met informatie over de conversie
    """
    result = {
        'success': False,
        'input_pages': 0,
        'output_pages': 0,
        'pages_added': 0,
        'format': None,
        'sheets': 0,
        'message': ''
    }
    
    try:
        # Open input PDF
        pdf = Pdf.open(input_path)
        original_page_count = len(pdf.pages)
        result['input_pages'] = original_page_count
        
        if verbose:
            print(f"📄 Input: {original_page_count} pagina's")
        
        if original_page_count == 0:
            result['message'] = 'PDF heeft geen pagina\'s'
            return result
        
        # Detecteer pagina formaat van eerste pagina
        first_page = pdf.pages[0]
        page_width, page_height, source_box = get_page_dimensions(first_page)
        
        # Zorg dat het portrait is (hoogte > breedte)
        if page_width > page_height:
            page_width, page_height = page_height, page_width
        
        # Bepaal formaat
        if format_override:
            page_format = format_override.upper()
        else:
            page_format = detect_format(page_width, page_height)
        
        result['format'] = page_format
        fmt = FORMATS[page_format]
        
        if verbose:
            print(f"📐 Formaat: {page_format} ({page_width/MM_TO_PT:.1f} x {page_height/MM_TO_PT:.1f} mm)")
        
        # === STAP 1: Pagina's aanvullen tot deelbaar door 4 ===
        pages_needed = original_page_count
        remainder = pages_needed % 4
        if remainder != 0:
            pages_needed = original_page_count + (4 - remainder)
        
        pages_to_add = pages_needed - original_page_count
        result['pages_added'] = pages_to_add
        
        if pages_to_add > 0:
            if verbose:
                print(f"➕ {pages_to_add} blanco pagina('s) toevoegen (totaal: {pages_needed})")
            
            # Maak blanco pagina's
            for _ in range(pages_to_add):
                # Maak een lege pagina met dezelfde afmetingen
                blank = pdf.make_indirect(
                    pikepdf.Dictionary(
                        Type=pikepdf.Name.Page,
                        MediaBox=[0, 0, fmt['page_width'], fmt['page_height']],
                        Resources=pikepdf.Dictionary()
                    )
                )
                pdf.pages.append(Page(blank))
        
        total_pages = len(pdf.pages)
        sheets = total_pages // 4
        result['sheets'] = sheets
        result['output_pages'] = sheets * 2  # Elk vel = 2 pagina's (voor + achter)
        
        if verbose:
            print(f"📑 Output: {sheets} vel(len), {result['output_pages']} pagina's")
        
        # === STAP 2: Bereken impositie volgorde ===
        imposition_order = calculate_imposition_order(total_pages)
        
        if verbose:
            print(f"🔢 Impositie volgorde:")
            for i, (left, right) in enumerate(imposition_order):
                side = "voorzijde" if i % 2 == 0 else "achterzijde"
                sheet_num = (i // 2) + 1
                print(f"   Vel {sheet_num} {side}: pagina {left} + {right}")
        
        # === STAP 3: Maak snijtekens overlay ===
        crop_marks_pdf_data = create_crop_marks_overlay(
            fmt['sheet_width'],
            fmt['sheet_height'],
            fmt['page_width'],
            fmt['page_height']
        )
        
        # Laad crop marks als PDF
        crop_marks_pdf = Pdf.open(BytesIO(crop_marks_pdf_data))
        crop_marks_page = crop_marks_pdf.pages[0]
        
        # === STAP 4: Maak output PDF ===
        output_pdf = Pdf.new()
        
        for spread_idx, (left_page_num, right_page_num) in enumerate(imposition_order):
            # Maak nieuw vel
            sheet = pikepdf.Dictionary(
                Type=pikepdf.Name.Page,
                MediaBox=[0, 0, fmt['sheet_width'], fmt['sheet_height']],
                Resources=pikepdf.Dictionary(),
                Contents=pikepdf.Stream(output_pdf, b'')
            )
            sheet_page = Page(output_pdf.make_indirect(sheet))
            
            # Bereken posities
            center_x = fmt['sheet_width'] / 2
            center_y = fmt['sheet_height'] / 2
            
            # Offset voor centreren
            y_offset = center_y - (fmt['page_height'] / 2)
            
            # Linker pagina (left_page_num is 1-indexed)
            left_idx = left_page_num - 1
            if 0 <= left_idx < len(pdf.pages):
                source_page = pdf.pages[left_idx]
                
                # Maak Form XObject van bronpagina
                form_xobj = sheet_page.add_content_as_form_xobject(
                    pdf_page=source_page,
                    name=f"/Page{left_page_num}"
                )
                
                # Plaats linker pagina
                x_pos = center_x - fmt['page_width']
                
                # Schaal berekenen indien nodig
                src_width, src_height, _ = get_page_dimensions(source_page)
                scale_x = fmt['page_width'] / src_width if src_width > 0 else 1
                scale_y = fmt['page_height'] / src_height if src_height > 0 else 1
                scale = min(scale_x, scale_y)
                
                # Content stream voor linker pagina
                content = f"q {scale} 0 0 {scale} {x_pos} {y_offset} cm /Page{left_page_num} Do Q\n"
                
                # Voeg toe aan sheet
                sheet_page.contents = pikepdf.Stream(
                    output_pdf,
                    sheet_page.contents.read_bytes() + content.encode()
                )
            
            # Rechter pagina
            right_idx = right_page_num - 1
            if 0 <= right_idx < len(pdf.pages):
                source_page = pdf.pages[right_idx]
                
                form_xobj = sheet_page.add_content_as_form_xobject(
                    pdf_page=source_page,
                    name=f"/Page{right_page_num}"
                )
                
                # Plaats rechter pagina
                x_pos = center_x
                
                src_width, src_height, _ = get_page_dimensions(source_page)
                scale_x = fmt['page_width'] / src_width if src_width > 0 else 1
                scale_y = fmt['page_height'] / src_height if src_height > 0 else 1
                scale = min(scale_x, scale_y)
                
                content = f"q {scale} 0 0 {scale} {x_pos} {y_offset} cm /Page{right_page_num} Do Q\n"
                
                sheet_page.contents = pikepdf.Stream(
                    output_pdf,
                    sheet_page.contents.read_bytes() + content.encode()
                )
            
            # Voeg snijtekens toe
            crop_form = sheet_page.add_content_as_form_xobject(
                pdf_page=crop_marks_page,
                name="/CropMarks"
            )
            content = "q /CropMarks Do Q\n"
            sheet_page.contents = pikepdf.Stream(
                output_pdf,
                sheet_page.contents.read_bytes() + content.encode()
            )
            
            output_pdf.pages.append(sheet_page)
        
        # Sla op
        output_pdf.save(output_path)
        
        result['success'] = True
        result['message'] = f"Impositie succesvol: {sheets} vel(len) op {fmt['name']}"
        
        if verbose:
            print(f"✅ Opgeslagen: {output_path}")
        
        return result
        
    except Exception as e:
        result['message'] = f"Fout: {str(e)}"
        if verbose:
            print(f"❌ Fout: {e}")
        import traceback
        traceback.print_exc()
        return result


def main():
    parser = argparse.ArgumentParser(
        description='Maak saddle stitch impositie van PDF voor boekjes',
        formatter_class=argparse.RawDescriptionHelpFormatter,
        epilog="""
Voorbeelden:
  python impose_booklet.py brochure.pdf brochure_imposed.pdf
  python impose_booklet.py --format A5 flyer.pdf flyer_print.pdf
  
Het script:
  - Detecteert TrimBox en snijdt indien nodig
  - Vult aan tot pagina's deelbaar door 4
  - Maakt saddle stitch impositie
  - Voegt snijtekens en vouwlijn toe
        """
    )
    
    parser.add_argument('input', help='Input PDF bestand')
    parser.add_argument('output', help='Output PDF bestand')
    parser.add_argument('--format', '-f', choices=['A4', 'A5'],
                        help='Forceer formaat (anders auto-detect)')
    parser.add_argument('--quiet', '-q', action='store_true',
                        help='Geen output naar console')
    
    args = parser.parse_args()
    
    if not Path(args.input).exists():
        print(f"❌ Bestand niet gevonden: {args.input}")
        sys.exit(1)
    
    result = impose_booklet(
        args.input,
        args.output,
        format_override=args.format,
        verbose=not args.quiet
    )
    
    sys.exit(0 if result['success'] else 1)


if __name__ == '__main__':
    main()
