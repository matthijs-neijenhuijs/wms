```instructions
# Warehouse Management System - AI Solutions & Innovation Guide

This instruction file defines AI-powered features and intelligent automation concepts for the warehouse management system. These guidelines should be followed when implementing AI-driven functionality across all modules.

---

## 1. Intelligent Demand Forecasting & Purchase Advice

### AI-Powered Demand Prediction
The system should implement machine learning models to predict future demand based on:
- **Historical sales patterns**: Analyze past order data to identify trends, seasonality, and cyclical patterns
- **External factors**: Weather data, holidays, promotional calendars, market events
- **Lead time analysis**: Factor in supplier lead times when calculating optimal reorder points
- **Safety stock optimization**: Dynamically adjust safety stock levels based on demand variability

### Smart Purchase Recommendations
```php
// Example structure for AI purchase advice
interface PurchaseAdvisor {
    public function calculateOptimalReorderPoint(Product $product): int;
    public function suggestOrderQuantity(Product $product, Carbon $targetDate): int;
    public function predictStockout(Product $product): ?Carbon;
    public function getSupplierRecommendation(Product $product): Supplier;
}
```

### Implementation Guidelines
- Store demand forecast data in a dedicated `demand_forecasts` table
- Use Laravel Jobs for background ML model training and prediction generation
- Provide confidence scores with each prediction
- Allow manual override with feedback loop to improve model accuracy

---

## 2. Intelligent Pick Route Optimization

### AI-Driven Picking Strategies
The system should optimize picker routes using algorithms that consider:
- **Warehouse topology**: Aisle layout, pick face locations, travel distances
- **Pick density**: Cluster orders with products in nearby locations
- **Picker capacity**: Weight limits, cart capacity, picker experience level
- **Time constraints**: Priority orders, carrier cutoff times

### Pick Stream Types to Support
1. **Single Order Picking**: One order per trip - for urgent/priority orders
2. **Batch Picking**: Multiple orders combined - AI groups similar orders
3. **Wave Picking**: Time-based batches aligned with shipping schedules
4. **Zone Picking**: AI assigns pickers to zones based on workload prediction
5. **Cluster Picking**: AI-optimized grouping for multi-order carts

### Route Optimization Model
```php
// Pick route optimization service structure
interface PickRouteOptimizer {
    public function optimizeRoute(Picklist $picklist): array;
    public function suggestBatchCombination(Collection $orders): Collection;
    public function calculateEstimatedPickTime(Picklist $picklist): int;
    public function assignOptimalPicker(Picklist $picklist): User;
}
```

### Location Coding Intelligence
- Implement AI-suggested location codes based on warehouse layout patterns
- Auto-detect and validate location format consistency
- Suggest optimal pick face assignments based on product velocity (ABC analysis)

---

## 3. ABC Analysis & Slotting Optimization

### Automated ABC Classification
The system should continuously analyze product movement and classify inventory:
- **A-items**: High velocity, 80% of picks, ~20% of SKUs - place near packing stations
- **B-items**: Medium velocity, 15% of picks, ~30% of SKUs - middle zone
- **C-items**: Low velocity, 5% of picks, ~50% of SKUs - remote storage

### AI Slotting Recommendations
```php
interface SlottingOptimizer {
    public function analyzeProductVelocity(Product $product, int $days = 90): VelocityScore;
    public function suggestOptimalLocation(Product $product): Location;
    public function generateRelocationPlan(): Collection;
    public function calculateSlottingImpact(): array; // time savings, efficiency gains
}
```

### Implementation Guidelines
- Run ABC analysis as a scheduled job (weekly recommended)
- Track velocity changes and alert when products shift classification
- Generate slotting reports with ROI calculations
- Support seasonal slotting adjustments

---

## 4. Intelligent Stock Allocation & Reservation

### Smart Order Allocation
The system should intelligently allocate stock considering:
- **FIFO/LIFO/FEFO**: First-In-First-Out, Last-In, First-Expiry for perishables
- **Location efficiency**: Allocate from pick faces before bulk storage
- **Order consolidation**: Reserve from same location when possible
- **Partial fulfillment rules**: AI decides optimal partial shipment strategies

### Allocation Priority Engine
```php
interface StockAllocator {
    public function allocateForOrder(Order $order): AllocationResult;
    public function suggestAllocationStrategy(Product $product): AllocationStrategy;
    public function handleStockContention(Collection $orders): Collection;
    public function optimizeReservations(): int; // returns freed stock count
}
```

### Reservation Intelligence
- Predict reservation timeout likelihood
- Auto-release stale reservations based on order patterns
- Smart re-allocation when stock becomes available

---

## 5. Return (RMA) Processing Intelligence

### AI-Powered Return Handling
```php
interface ReturnProcessor {
    public function predictReturnCondition(Return $return): ConditionPrediction;
    public function suggestDisposition(Return $return): Disposition; // restock, refurbish, dispose
    public function calculateRefurbishmentCost(Return $return): Money;
    public function detectReturnFraud(Return $return): FraudScore;
}
```

### Return Reason Analysis
- Categorize return reasons with NLP analysis of customer comments
- Identify product quality issues through return pattern analysis
- Generate supplier quality scorecards based on return data
- Predict return rates for new products based on similar items

### Quarantine & Reconditioning Workflow
- AI-driven inspection checklists based on product type
- Automated grading system for returned items
- Smart routing to reconditioning or disposal

---

## 6. Inbound Receipt Intelligence

### Smart Receiving
```php
interface InboundProcessor {
    public function predictReceiptTiming(PurchaseOrder $po): Carbon;
    public function suggestPutawayLocations(Receipt $receipt): Collection;
    public function detectQuantityDiscrepancies(Receipt $receipt): Collection;
    public function optimizeCrossDocking(Receipt $receipt): ?Collection;
}
```

### Cross-Docking Optimization
- Automatically identify inbound items with immediate outbound demand
- Route cross-dock items directly to packing without putaway
- Calculate cross-dock eligibility based on order timing and carrier schedules

### Inbound Forecast Integration
- Predict warehouse capacity needs based on expected receipts
- Alert when receiving capacity is at risk
- Schedule receipt appointments based on workload prediction

---

## 7. Cycle Count & Inventory Accuracy

### AI-Driven Cycle Count Planning
```php
interface CycleCountPlanner {
    public function prioritizeCountLocations(): Collection;
    public function predictDiscrepancyRisk(Location $location): float;
    public function scheduleOptimalCountTimes(): Collection;
    public function analyzeCountResults(CycleCount $count): DiscrepancyAnalysis;
}
```

### Intelligent Count Scheduling
- Prioritize high-value and high-velocity items
- Schedule counts during low-activity periods
- Focus on locations with historical discrepancy patterns
- Implement perpetual inventory with AI variance detection

### Root Cause Analysis
- Identify patterns in inventory discrepancies
- Correlate discrepancies with specific pickers, shifts, or processes
- Generate recommendations for process improvements

---

## 8. Carrier & Shipping Intelligence

### Smart Carrier Selection
```php
interface CarrierOptimizer {
    public function selectOptimalCarrier(Order $order): Carrier;
    public function predictDeliveryTime(Order $order, Carrier $carrier): Carbon;
    public function calculateShippingCost(Order $order): Collection; // all carrier options
    public function suggestConsolidation(Collection $orders): Collection;
}
```

### Shipping Optimization Features
- Rate shop across carriers in real-time
- Predict delivery success probability by address
- Suggest optimal ship dates to meet delivery promises
- Identify orders that can be consolidated for multi-package discounts

### Cutoff Time Management
- Dynamic cutoff times based on current workload
- Carrier pickup schedule optimization
- Alert when orders are at risk of missing cutoff

---

## 9. Workforce Management Intelligence

### AI Workload Prediction
```php
interface WorkforceOptimizer {
    public function predictDailyWorkload(Carbon $date): WorkloadForecast;
    public function suggestStaffingLevels(Carbon $date): StaffingPlan;
    public function assignTasksOptimally(Collection $tasks, Collection $workers): Collection;
    public function trackProductivity(User $worker): ProductivityMetrics;
}
```

### Productivity Analytics
- Track picks per hour, accuracy rates, travel time
- Identify training opportunities for underperforming areas
- Gamification elements for picker performance
- Fair task distribution based on skill and capacity

---

## 10. Anomaly Detection & Alerts

### Intelligent Monitoring
```php
interface AnomalyDetector {
    public function detectOrderAnomalies(Order $order): Collection;
    public function monitorStockLevels(): Collection; // unusual movements
    public function identifyProcessBottlenecks(): Collection;
    public function predictEquipmentIssues(): Collection;
}
```

### Alert Categories
- **Stock anomalies**: Unusual depletion, negative stock, ghost inventory
- **Order anomalies**: Fraudulent patterns, unusual quantities, suspicious addresses
- **Process anomalies**: Pick errors, shipping delays, receiving discrepancies
- **Capacity alerts**: Storage utilization, processing backlogs

---

## 11. Natural Language Interface

### Conversational Warehouse Assistant
Implement an AI assistant that warehouse staff can interact with using natural language:

```php
interface WarehouseAssistant {
    public function processQuery(string $query, User $user): AssistantResponse;
    public function executeCommand(string $command, User $user): CommandResult;
    public function explainDecision(string $context): string;
}
```

### Example Interactions
- "What's the stock level for SKU ABC123?"
- "Where should I put away these 50 units of product X?"
- "Show me orders at risk of missing today's cutoff"
- "Why was this order allocated from location B instead of A?"

---

## 12. Predictive Maintenance & Hardware Integration

### Equipment Intelligence
- Predict scanner battery failures
- Monitor printer performance and predict maintenance needs
- Track conveyor and automation system health
- Integration with IoT sensors for environmental monitoring

---

## Database Schema Recommendations for AI Features

### New Tables to Consider
```
- demand_forecasts (product_id, forecast_date, predicted_quantity, confidence, model_version)
- product_velocity_scores (product_id, abc_class, velocity_score, calculated_at)
- pick_route_metrics (picklist_id, estimated_time, actual_time, distance, picker_id)
- return_predictions (return_id, condition_prediction, fraud_score, disposition_suggestion)
- anomaly_logs (type, entity_type, entity_id, severity, description, detected_at)
- workforce_productivity (user_id, date, picks_per_hour, accuracy_rate, tasks_completed)
- carrier_performance (carrier_id, date, on_time_rate, damage_rate, avg_cost)
- slotting_recommendations (product_id, current_location_id, suggested_location_id, impact_score)
```

---

## Integration Points

### External AI Services
- Consider integration with cloud ML services (AWS SageMaker, Google AI, Azure ML)
- Implement fallback logic when AI services are unavailable
- Cache predictions for performance

### Data Pipeline
- Use Laravel Queues for async ML processing
- Implement data warehousing for historical analysis
- Consider event sourcing for comprehensive audit trails

---

## Best Practices

1. **Explainability**: Always provide reasoning for AI decisions
2. **Override capability**: Allow human override of all AI suggestions
3. **Feedback loops**: Capture outcomes to improve model accuracy
4. **Gradual rollout**: Implement AI features with A/B testing capability
5. **Performance monitoring**: Track AI decision quality metrics
6. **Privacy compliance**: Ensure GDPR compliance for all data processing
7. **Fail-safe defaults**: System must function without AI when needed

---

## Testing AI Features

When writing tests for AI-powered features:
- Test with edge cases and boundary conditions
- Mock ML model responses for deterministic testing
- Include performance benchmarks
- Test fallback behavior when AI is unavailable
- Validate that human overrides are properly recorded

```
