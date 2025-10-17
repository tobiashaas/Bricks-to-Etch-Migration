#!/usr/bin/env python3
"""
Analyze Etch post_content structure
"""

import sys
import json
import re

# Read content from stdin
content = sys.stdin.read()

print("=" * 80)
print("ETCH GUTENBERG BLOCK STRUCTURE")
print("=" * 80)
print()

# Extract first wp:group block
group_pattern = r'<!-- wp:group \{([^}]+(?:\{[^}]+\})*[^}]*)\} -->'
matches = re.finditer(group_pattern, content)

for i, match in enumerate(matches):
    if i >= 3:  # Only show first 3
        break
    
    block_json = '{' + match.group(1) + '}'
    
    try:
        # Try to parse as JSON (might fail due to nested braces)
        # Let's just show the raw structure
        print(f"Block {i+1}:")
        print("-" * 80)
        
        # Extract key parts
        if '"tagName"' in block_json:
            tag_match = re.search(r'"tagName":"([^"]+)"', block_json)
            if tag_match:
                print(f"Tag: {tag_match.group(1)}")
        
        if '"metadata"' in block_json:
            name_match = re.search(r'"name":"([^"]+)"', block_json)
            if name_match:
                print(f"Name: {name_match.group(1)}")
            
            # Extract etchData
            etch_match = re.search(r'"etchData":\{([^}]+(?:\{[^}]+\})*)\}', block_json)
            if etch_match:
                etch_str = '{' + etch_match.group(1) + '}'
                print(f"etchData (raw): {etch_str[:300]}...")
                
                # Try to extract key fields
                origin = re.search(r'"origin":"([^"]+)"', etch_str)
                styles = re.search(r'"styles":\[([^\]]+)\]', etch_str)
                attrs = re.search(r'"attributes":\{([^}]+)\}', etch_str)
                block_type = re.search(r'"block":\{([^}]+)\}', etch_str)
                
                if origin:
                    print(f"  - origin: {origin.group(1)}")
                if styles:
                    print(f"  - styles: [{styles.group(1)}]")
                if attrs:
                    print(f"  - attributes: {{{attrs.group(1)}}}")
                if block_type:
                    print(f"  - block: {{{block_type.group(1)}}}")
        
        print()
    except Exception as e:
        print(f"Error parsing block {i+1}: {e}")
        print()

print()
print("=" * 80)
print("CLEAN EXAMPLE - Section Block")
print("=" * 80)

# Find the Hero section
hero_match = re.search(
    r'<!-- wp:group \{("tagName":"section"[^}]+metadata[^}]+Hero[^}]+etchData[^}]+(?:\{[^}]+\})*[^}]*)\} -->',
    content
)

if hero_match:
    print("Found Hero section!")
    print()
    hero_json = '{' + hero_match.group(1) + '}'
    
    # Pretty print key parts
    print("Structure:")
    print("  tagName: section")
    print("  metadata:")
    print("    name: Hero")
    print("    etchData:")
    
    # Extract etchData fields
    etch_match = re.search(r'"etchData":\{([^}]+(?:\{[^}]+\})*)\}', hero_json)
    if etch_match:
        etch_str = '{' + etch_match.group(1) + '}'
        
        # Extract each field
        for field in ['origin', 'name']:
            match = re.search(f'"{field}":"([^"]+)"', etch_str)
            if match:
                print(f"      {field}: {match.group(1)}")
        
        # Styles array
        styles_match = re.search(r'"styles":\[([^\]]+)\]', etch_str)
        if styles_match:
            print(f"      styles: [{styles_match.group(1)}]")
        
        # Attributes object
        attrs_match = re.search(r'"attributes":\{([^}]+)\}', etch_str)
        if attrs_match:
            print(f"      attributes: {{{attrs_match.group(1)}}}")
        
        # Block object
        block_match = re.search(r'"block":\{([^}]+)\}', etch_str)
        if block_match:
            print(f"      block: {{{block_match.group(1)}}}")
