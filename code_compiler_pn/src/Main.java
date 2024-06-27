package org.example;

import java.util.*;
import org.json.*;

public class Main {
    public static void main(String[] args) {
        Solution solution = new Solution();
        Object result = solution.createValue();
        System.out.println(new JSONObject().put("result", result));
    }
}

class Solution {
    public List<String> createValue() {
        List<String> values = new ArrayList<>();
        values.add("value1");
        values.add("value2");
        return values;
    }
}

