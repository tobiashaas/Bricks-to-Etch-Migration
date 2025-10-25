#!/bin/bash
# Script to rename all B2E_ classes to EFS_ with backward compatibility

# Find all PHP files in includes directory
find bricks-etch-migration/includes -name "*.php" -type f | while read file; do
    echo "Processing: $file"
    
    # Rename class declarations
    sed -i 's/class B2E_/class EFS_/g' "$file"
    
    # Rename type hints in function parameters
    sed -i 's/B2E_Error_Handler/EFS_Error_Handler/g' "$file"
    sed -i 's/B2E_API_Client/EFS_API_Client/g' "$file"
    sed -i 's/B2E_Content_Parser/EFS_Content_Parser/g' "$file"
    sed -i 's/B2E_CSS_Converter/EFS_CSS_Converter/g' "$file"
    sed -i 's/B2E_Gutenberg_Generator/EFS_Gutenberg_Generator/g' "$file"
    sed -i 's/B2E_Dynamic_Data_Converter/EFS_Dynamic_Data_Converter/g' "$file"
    sed -i 's/B2E_Media_Migrator/EFS_Media_Migrator/g' "$file"
    sed -i 's/B2E_Plugin_Detector/EFS_Plugin_Detector/g' "$file"
    sed -i 's/B2E_Migration_Service/EFS_Migration_Service/g' "$file"
    sed -i 's/B2E_CSS_Service/EFS_CSS_Service/g' "$file"
    sed -i 's/B2E_Content_Service/EFS_Content_Service/g' "$file"
    sed -i 's/B2E_Media_Service/EFS_Media_Service/g' "$file"
    sed -i 's/B2E_Base_Element/EFS_Base_Element/g' "$file"
    sed -i 's/B2E_Element_Factory/EFS_Element_Factory/g' "$file"
    sed -i 's/B2E_Service_Container/EFS_Service_Container/g' "$file"
    
    # Add class alias if class declaration found and not already present
    if grep -q "^class EFS_" "$file" && ! grep -q "class_alias.*EFS_.*B2E_" "$file"; then
        # Extract class name
        class_name=$(grep "^class EFS_" "$file" | head -1 | sed 's/class EFS_//' | sed 's/ .*//')
        old_name="B2E_$class_name"
        new_name="EFS_$class_name"
        
        # Add alias before closing PHP tag or at end of file
        if grep -q "^?>" "$file"; then
            sed -i "/^?>/i\\\\n// Legacy alias for backward compatibility\\nclass_alias( __NAMESPACE__ . '\\\\\\\\$new_name', __NAMESPACE__ . '\\\\\\\\$old_name' );" "$file"
        else
            echo -e "\n// Legacy alias for backward compatibility\nclass_alias( __NAMESPACE__ . '\\\\$new_name', __NAMESPACE__ . '\\\\$old_name' );" >> "$file"
        fi
    fi
done

echo "Renaming complete!"
