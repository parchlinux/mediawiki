#!/usr/bin/env python3
"""
Parch Linux Wiki.js to MediaWiki Migration Tool

Converts Wiki.js Markdown articles, frontmatter metadata, tags, code blocks,
callout alerts, internal/external links, tables, and asset references into a
standard MediaWiki XML import dump (export.xml).

Usage:
    python3 maintenance/migrate-wikijs.py \
        --input /path/to/wikijs/backup \
        --output export.xml \
        --assets-out /path/to/imported_images \
        --site-name "Parch Linux Wiki"
"""

import argparse
import datetime
import html
import os
import re
import shutil
import sys
from pathlib import Path
from typing import Dict, List, Optional, Tuple


def parse_frontmatter(content: str) -> Tuple[Dict[str, str], str]:
    """Extracts YAML frontmatter metadata and body from markdown content."""
    metadata = {}
    body = content

    frontmatter_match = re.match(r"^---\s*\n(.*?)\n---\s*\n", content, re.DOTALL)
    if frontmatter_match:
        raw_yaml = frontmatter_match.group(1)
        body = content[frontmatter_match.end():]

        for line in raw_yaml.splitlines():
            line = line.strip()
            if not line or line.startswith("#"):
                continue
            if ":" in line:
                key, val = line.split(":", 1)
                key = key.strip()
                val = val.strip().strip('"').strip("'")
                
                # Parse list format like [tag1, tag2]
                if val.startswith("[") and val.endswith("]"):
                    val_items = [item.strip().strip('"').strip("'") for item in val[1:-1].split(",") if item.strip()]
                    metadata[key] = val_items
                else:
                    metadata[key] = val

    return metadata, body


def convert_callouts(wikitext: str) -> str:
    """Converts GitHub/Wiki.js callout alerts to Parch MediaWiki templates."""
    # Convert Wiki.js ::: tip / ::: warning / ::: info blocks
    def replace_triple_colon(match):
        c_type = match.group(1).lower()
        content = match.group(2).strip()
        template_map = {
            "tip": "Tip",
            "warning": "Warning",
            "danger": "Warning",
            "info": "Note",
            "note": "Note",
            "caution": "Warning",
        }
        tpl = template_map.get(c_type, "Note")
        return f"{{{{{tpl}|{content}}}}}"

    wikitext = re.sub(
        r":::\s*(tip|warning|danger|info|note|caution)\s*\n(.*?)\n:::",
        replace_triple_colon,
        wikitext,
        flags=re.DOTALL | re.IGNORECASE,
    )

    # Convert GitHub markdown alerts (> [!NOTE], > [!TIP], > [!WARNING])
    def replace_gh_alert(match):
        a_type = match.group(1).upper()
        content_lines = match.group(2).strip().splitlines()
        clean_lines = [re.sub(r"^>\s?", "", l) for l in content_lines]
        content = "\n".join(clean_lines).strip()
        template_map = {
            "NOTE": "Note",
            "TIP": "Tip",
            "WARNING": "Warning",
            "IMPORTANT": "Important",
            "CAUTION": "Caution",
        }
        tpl = template_map.get(a_type, "Note")
        return f"{{{{{tpl}|{content}}}}}"

    wikitext = re.sub(
        r"^>\s*\[!(NOTE|TIP|WARNING|IMPORTANT|CAUTION)\]\s*\n((?:>.*(?:\n|$))*)",
        replace_gh_alert,
        wikitext,
        flags=re.MULTILINE | re.IGNORECASE,
    )

    return wikitext


def convert_tables(text: str) -> str:
    """Converts Markdown tables into MediaWiki wikitables."""
    table_pattern = re.compile(
        r"((?:^\|[^\n]+\|\r?\n)(?:^\|[\s\-:|]+\|\r?\n)(?:^\|[^\n]+\|\r?\n?)+)",
        re.MULTILINE,
    )

    def replace_table(match):
        raw_table = match.group(1).strip().splitlines()
        if len(raw_table) < 2:
            return match.group(0)

        header_line = raw_table[0]
        separator_line = raw_table[1]
        data_lines = raw_table[2:]

        headers = [c.strip() for c in header_line.split("|")[1:-1]]
        
        output = ['{| class="wikitable"']
        # Header row
        output.append("! " + " !! ".join(headers))

        for row in data_lines:
            cells = [c.strip() for c in row.split("|")[1:-1]]
            output.append("|-")
            output.append("| " + " || ".join(cells))

        output.append("|}")
        return "\n" + "\n".join(output) + "\n"

    return table_pattern.sub(replace_table, text)


def convert_links_and_images(text: str, source_file: Path, assets_src_dir: Optional[Path], assets_dest_dir: Optional[Path]) -> str:
    """Rewrites image links, internal wiki links, and external links."""
    
    # 1. Images: ![alt](path/image.png)
    def replace_image(match):
        alt = match.group(1) or ""
        img_path_str = match.group(2).strip()

        # Extract filename
        filename = os.path.basename(img_path_str.split("?")[0].split("#")[0])
        # Clean filename
        clean_filename = re.sub(r"[^\w\.-]", "_", filename)

        # Copy asset if directories provided
        if assets_dest_dir:
            assets_dest_dir.mkdir(parents=True, exist_ok=True)
            # Look for asset in source dir or relative to file
            candidate_paths = [
                source_file.parent / img_path_str,
                source_file.parent / filename,
            ]
            if assets_src_dir:
                candidate_paths.extend([
                    assets_src_dir / img_path_str.lstrip("/"),
                    assets_src_dir / filename,
                ])

            for src in candidate_paths:
                if src.is_file():
                    dest = assets_dest_dir / clean_filename
                    try:
                        shutil.copy2(src, dest)
                    except Exception:
                        pass
                    break

        if alt:
            return f"[[File:{clean_filename}|thumb|alt={alt}|{alt}]]"
        return f"[[File:{clean_filename}|thumb]]"

    text = re.sub(r"!\[(.*?)\]\((.*?)\)", replace_image, text)

    # 2. Markdown Links: [title](url)
    def replace_link(match):
        title = match.group(1)
        target = match.group(2).strip()

        # External links
        if target.startswith("http://") or target.startswith("https://") or target.startswith("//"):
            return f"[{target} {title}]"

        # Internal wiki links
        clean_target = target.lstrip("/")
        if clean_target.endswith(".md"):
            clean_target = clean_target[:-3]

        # Convert slash paths to Title Case / Wiki namespace
        parts = [p.capitalize() for p in clean_target.split("/") if p]
        wiki_title = "/".join(parts)

        if not wiki_title:
            return title

        if wiki_title.lower() == title.lower():
            return f"[[{wiki_title}]]"
        return f"[[{wiki_title}|{title}]]"

    text = re.sub(r"\[(.*?)\]\((.*?)\)", replace_link, text)

    return text


def markdown_to_wikitext(md_content: str, source_file: Path, assets_src_dir: Optional[Path], assets_dest_dir: Optional[Path]) -> str:
    """Full parser converting Markdown document to MediaWiki wikitext."""
    
    # 1. Protect and convert code blocks
    code_blocks = []

    def extract_code_block(match):
        lang = match.group(1) or "text"
        code = match.group(2)
        idx = len(code_blocks)
        replacement = f"%%%CODE_BLOCK_{idx}%%%"
        code_blocks.append(f'<syntaxhighlight lang="{lang}">\n{code}\n</syntaxhighlight>')
        return replacement

    text = re.sub(r"```([a-zA-Z0-9_\-#+]*)\n(.*?)\n```", extract_code_block, md_content, flags=re.DOTALL)

    # 2. Convert Callouts / Alerts
    text = convert_callouts(text)

    # 3. Convert Markdown Tables
    text = convert_tables(text)

    # 4. Convert Links and Images
    text = convert_links_and_images(text, source_file, assets_src_dir, assets_dest_dir)

    # 5. Headings (# -> =, ## -> ==, etc.)
    text = re.sub(r"^######\s+(.*?)\s*#*$", r"====== \1 ======", text, flags=re.MULTILINE)
    text = re.sub(r"^#####\s+(.*?)\s*#*$", r"===== \1 =====", text, flags=re.MULTILINE)
    text = re.sub(r"^####\s+(.*?)\s*#*$", r"==== \1 ====", text, flags=re.MULTILINE)
    text = re.sub(r"^###\s+(.*?)\s*#*$", r"=== \1 ===", text, flags=re.MULTILINE)
    text = re.sub(r"^##\s+(.*?)\s*#*$", r"== \1 ==", text, flags=re.MULTILINE)
    text = re.sub(r"^#\s+(.*?)\s*#*$", r"= \1 =", text, flags=re.MULTILINE)

    # 6. Inline code
    text = re.sub(r"`([^`\n]+)`", r"<code>\1</code>", text)

    # 7. Bold and Italic
    text = re.sub(r"\*\*\*(.*?)\*\*\*", r"'''''\1'''''", text)
    text = re.sub(r"\*\*(.*?)\*\*", r"'''\1'''", text)
    text = re.sub(r"__([^_]+)__", r"'''\1'''", text)
    text = re.sub(r"\*([^*\n]+)\*", r"''\1''", text)
    text = re.sub(r"_([^_\n]+)_", r"''\1''", text)

    # 8. Unordered Lists
    text = re.sub(r"^(\s*)[-\*+]\s+(.*)$", lambda m: ("*" * (len(m.group(1)) // 2 + 1)) + " " + m.group(2), text, flags=re.MULTILINE)

    # 9. Ordered Lists
    text = re.sub(r"^(\s*)\d+\.\s+(.*)$", lambda m: ("#" * (len(m.group(1)) // 2 + 1)) + " " + m.group(2), text, flags=re.MULTILINE)

    # 10. Horizontal Rules
    text = re.sub(r"^(\*{3,}|-{3,}|_{3,})$", r"----", text, flags=re.MULTILINE)

    # 11. Restore code blocks
    for idx, cb in enumerate(code_blocks):
        text = text.replace(f"%%%CODE_BLOCK_{idx}%%%", cb)

    return text.strip()


def build_page_title(file_path: Path, input_root: Path, metadata: Dict) -> str:
    """Determines canonical MediaWiki title for the article."""
    if "title" in metadata and metadata["title"]:
        title = str(metadata["title"]).strip()
        # If path indicates subpage or language
        rel_path = file_path.relative_to(input_root)
        parts = list(rel_path.parts[:-1])
        if parts:
            namespace_prefix = "/".join(p.capitalize() for p in parts)
            if not title.startswith(namespace_prefix):
                return f"{namespace_prefix}/{title}"
        return title

    # Fallback to filepath title
    rel_path = file_path.relative_to(input_root)
    parts = list(rel_path.parts)
    # Remove .md
    parts[-1] = parts[-1].replace(".md", "")
    return "/".join(p.replace("-", " ").replace("_", " ").title() for p in parts)


def generate_mediawiki_xml(pages: List[Dict], site_name: str = "Parch Linux Wiki") -> str:
    """Builds valid MediaWiki XML export dump (0.11 schema)."""
    now_iso = datetime.datetime.now(datetime.timezone.utc).strftime("%Y-%m-%dT%H:%M:%SZ")

    xml_lines = [
        '<mediawiki xmlns="http://www.mediawiki.org/xml/export-0.11/"',
        '           xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"',
        '           xsi:schemaLocation="http://www.mediawiki.org/xml/export-0.11/ http://www.mediawiki.org/xml/export-0.11.xsd"',
        '           version="0.11" xml:lang="en">',
        '  <siteinfo>',
        f'    <sitename>{html.escape(site_name)}</sitename>',
        '    <dbname>parchwiki</dbname>',
        '    <base>https://wiki.parchlinux.com/wiki/Main_Page</base>',
        '    <generator>Parch Wiki.js Importer 1.0</generator>',
        '    <case>first-letter</case>',
        '    <namespaces>',
        '      <namespace key="-2" case="first-letter">Media</namespace>',
        '      <namespace key="-1" case="first-letter">Special</namespace>',
        '      <namespace key="0" case="first-letter" />',
        '      <namespace key="1" case="first-letter">Talk</namespace>',
        '      <namespace key="2" case="first-letter">User</namespace>',
        '      <namespace key="3" case="first-letter">User talk</namespace>',
        '      <namespace key="4" case="first-letter">Project</namespace>',
        '      <namespace key="5" case="first-letter">Project talk</namespace>',
        '      <namespace key="6" case="first-letter">File</namespace>',
        '      <namespace key="7" case="first-letter">File talk</namespace>',
        '      <namespace key="10" case="first-letter">Template</namespace>',
        '      <namespace key="14" case="first-letter">Category</namespace>',
        '      <namespace key="15" case="first-letter">Category talk</namespace>',
        '    </namespaces>',
        '  </siteinfo>',
    ]

    for page_id, page in enumerate(pages, start=1):
        title = page["title"]
        wikitext = page["content"]
        timestamp = page.get("timestamp", now_iso)

        xml_lines.extend([
            '  <page>',
            f'    <title>{html.escape(title)}</title>',
            '    <ns>0</ns>',
            f'    <id>{page_id}</id>',
            '    <revision>',
            f'      <id>{page_id}</id>',
            f'      <timestamp>{timestamp}</timestamp>',
            '      <contributor>',
            '        <username>Wiki.js Migration</username>',
            '        <id>1</id>',
            '      </contributor>',
            '      <comment>Imported from Wiki.js backup</comment>',
            '      <model>wikitext</model>',
            '      <format>text/x-wiki</format>',
            f'      <text bytes="{len(wikitext.encode("utf-8"))}" xml:space="preserve">{html.escape(wikitext)}</text>',
            '    </revision>',
            '  </page>',
        ])

    xml_lines.append('</mediawiki>')
    return "\n".join(xml_lines)


def process_migration(input_dir: Path, output_xml: Path, assets_src_dir: Optional[Path], assets_dest_dir: Optional[Path], site_name: str):
    """Recursively processes Wiki.js markdown repository and creates export.xml."""
    if not input_dir.exists():
        print(f"Error: Input directory does not exist: {input_dir}", file=sys.stderr)
        sys.exit(1)

    pages = []
    md_files = sorted(list(input_dir.rglob("*.md")))
    print(f"==> Found {len(md_files)} Markdown files in {input_dir}")

    for file_path in md_files:
        try:
            raw_content = file_path.read_text(encoding="utf-8")
        except Exception as e:
            print(f"Warning: Failed to read {file_path}: {e}", file=sys.stderr)
            continue

        metadata, body = parse_frontmatter(raw_content)
        title = build_page_title(file_path, input_dir, metadata)

        # Convert content
        wikitext = markdown_to_wikitext(body, file_path, assets_src_dir, assets_dest_dir)

        # Append tags / categories
        tags = metadata.get("tags", [])
        if isinstance(tags, str):
            tags = [t.strip() for t in tags.split(",") if t.strip()]
        
        category_markup = []
        for tag in tags:
            tag_title = tag.replace("-", " ").title()
            category_markup.append(f"[[Category:{tag_title}]]")

        if category_markup:
            wikitext = wikitext + "\n\n" + " ".join(category_markup)

        pages.append({
            "title": title,
            "content": wikitext,
            "timestamp": datetime.datetime.now(datetime.timezone.utc).strftime("%Y-%m-%dT%H:%M:%SZ"),
        })

    # Generate XML
    xml_content = generate_mediawiki_xml(pages, site_name)
    output_xml.parent.mkdir(parents=True, exist_ok=True)
    output_xml.write_text(xml_content, encoding="utf-8")
    print(f"==> Successfully generated MediaWiki XML dump with {len(pages)} pages at: {output_xml}")
    if assets_dest_dir and assets_dest_dir.exists():
        img_count = len(list(assets_dest_dir.glob("*")))
        print(f"==> Extracted {img_count} media assets to: {assets_dest_dir}")


def main():
    parser = argparse.ArgumentParser(description="Parch Linux Wiki.js to MediaWiki Migration Tool")
    parser.add_argument("--input", "-i", required=True, type=Path, help="Path to Wiki.js markdown backup directory")
    parser.add_argument("--output", "-o", default=Path("maintenance/export.xml"), type=Path, help="Output MediaWiki XML dump path")
    parser.add_argument("--assets-src", type=Path, default=None, help="Source path for Wiki.js media/asset files")
    parser.add_argument("--assets-out", type=Path, default=Path("images/imported"), help="Destination directory to copy extracted assets")
    parser.add_argument("--site-name", default="Parch Linux Wiki", help="MediaWiki site name for XML header")

    args = parser.parse_args()
    process_migration(args.input, args.output, args.assets_src, args.assets_out, args.site_name)


if __name__ == "__main__":
    main()
