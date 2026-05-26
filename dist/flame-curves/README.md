# Flame Curves

The animated flame stroke loads curve files from this folder in sequence:

- `curve-001.json`
- `curve-002.json`
- `curve-003.json`

To add another pose, copy an existing curve file, name it with the next number, and edit the point coordinates. The loader stops at the first missing number, so keep the numbering continuous.

Each file must contain the same number of nodes, in the same command order. Cubic curve nodes use `a`, `c1`, and `c2`; move nodes use `a` with `c1` and `c2` as `null`.
