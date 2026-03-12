import re

with open('/Users/emre/Desktop/Emare/emarepos/pos-system/resources/views/pos/branches/index.blade.php', 'r') as f:
    content = f.read()

# Replace the modal container class strings
old_str = '<div class="relative bg-white w-full sm:rounded-2xl border border-gray-100 shadow-2xl max-w-5xl h-full sm:h-auto max-h-[95vh] flex flex-col my-auto" x-transition @click.stop>'
new_str = '<div class="relative bg-white w-full sm:rounded-2xl border border-gray-100 shadow-2xl max-w-5xl max-h-[95vh] flex flex-col m-auto" x-transition @click.stop>'
content = content.replace(old_str, new_str)

with open('/Users/emre/Desktop/Emare/emarepos/pos-system/resources/views/pos/branches/index.blade.php', 'w') as f:
    f.write(content)
