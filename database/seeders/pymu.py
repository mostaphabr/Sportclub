import fitz

pdf_path = "C:\Users\alikl\Downloads\wifi.pdf" ; 
doc = fitz.open(pdf_path)

page = doc[0]

